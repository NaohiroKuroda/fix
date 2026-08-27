<?php

namespace App\Repositories;

use App\Models\AdminUser;
use App\Models\TBuilding;
use App\Models\TBuildingBudgetItem;
use App\Models\TPayablePartner;
use App\Repositories\Contracts\QuotationRepositoryInterface;
use App\Services\FelixTotal\FelixTotalQuoteRequestGateway;
use App\Utils\Blame;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * 新スキーマ（t_buildings / t_building_budget_items / t_payable_partners /
 * t_payable_quotations）からの見積管理データ取得。
 *
 * 2026-08 のスキーマ改訂で見積先が支払／請求で分割された（支払＝t_payable_partners /
 * 請求＝t_billing_partners）。**本リポジトリは支払（はらい）側のみを扱う**。
 * 請求（もらい）は専用の【請求】系画面が扱う（docs/detailed-design/quotations/06〜09）。
 *
 * 旧 felix_total スキーマ版（{@see QuotationRepository}）と同じ {@see QuotationRepositoryInterface}
 * を実装し、サイドメニューのデータソース切替で差し替えて使う。
 *
 * 画面（mode）振り分けは approval_status による（approver_id 列は未追加のため）。
 * 見積依頼（quote-request）は新スキーマに履歴テーブルが無いため、旧 estimate_order_histories の
 * 有無で未依頼を判定し、送信は felix_total の order_estimate をサーバ間 HTTP で実行する
 * （{@see FelixTotalQuoteRequestGateway} / docs/architecture/backend.md「3.5 外部システム連携」）。
 */
class BuildingQuotationRepository implements QuotationRepositoryInterface
{
    /**
     * mode → **操作できる** approval_status（処理フロー J列「表示承認ステータス」）。
     *
     * 一覧にはこれ以外のステータスも出す（K列「ステータス外表示形式」＝出すが操作させない）。
     * 操作可否は Resource の `operable` としてフロントへ渡す。
     */
    private const MODE_OPERABLE_STATUS = [
        'quote-request' => ['DRAFT', 'CANCELLED'],   // 未申請 / 取消承認済
        'vendor-selection' => ['DRAFT'],             // 未申請（かつ業者回答あり）
        'manager-approval' => ['APPLIED'],           // 申請中（承認待ち）
        'cancel-request' => ['APPROVED'],            // 承認済
        'cancel-approval' => ['CANCEL_APPLIED'],     // 取消申請中
    ];

    /**
     * 画面の既定の区分（処理フロー H列「区分が支払」）。
     * 支払系5画面はいずれも既定 `payable`。画面上のトグルで `billing`（請求）へ切り替えると
     * **請求取引先を表示のみで参照**できる（操作は不可）。
     */
    private const DEFAULT_KIND = 'payable';

    /**
     * mode → 一覧から**除外する**（表示すらしない）画面。
     *
     * 【支払】部長取消承認だけは K列が「以外のステータスのデータは表示しない」のため、
     * 操作対象のステータスで一覧そのものを絞る。
     */
    private const MODE_HIDES_OTHER_STATUS = ['cancel-approval'];

    public function __construct(
        private readonly FelixTotalQuoteRequestGateway $felix,
    ) {}

    public function forEstimateManagement(array $filters, int $perPage, string $mode): LengthAwarePaginator
    {
        $keyword = $this->nonEmpty($filters['keyword'] ?? null);     // 実行予算名（t_buildings.name）
        $itemLabel = $this->nonEmpty($filters['itemLabel'] ?? null); // 項目名（t_building_budget_items.name）
        $vendor = $this->nonEmpty($filters['vendor'] ?? null);       // 見積先（company_name）
        $answer = (string) ($filters['answer'] ?? 'all');            // 業者選定の回答状態（既定=全て）
        $comment = (string) ($filters['comment'] ?? 'all');          // コメント有無（all/has/none）
        // 区分。payable=支払のみ / billing=請求のみ / all=両方を同じ一覧に並べる。
        // 支払系画面の既定は payable。all のとき請求行は表示のみ（操作不可）。
        $kind = (string) ($filters['kind'] ?? self::DEFAULT_KIND);
        if (! in_array($kind, ['all', 'payable', 'billing'], true)) {
            $kind = self::DEFAULT_KIND;
        }
        $withPayable = $kind !== 'billing';
        $withBilling = $kind !== 'payable';
        /** @var list<string> $relations 読み込む取引先リレーション（all は両方）。 */
        $relations = array_values(array_filter([
            $withPayable ? 'payablePartners' : null,
            $withBilling ? 'billingPartners' : null,
        ]));
        // 本リポジトリは支払系画面のもの。自区分＝支払（t_payable_partners）。
        $screenIsBilling = false;
        $ownRelation = $screenIsBilling ? 'billingPartners' : 'payablePartners';
        // 取引先レベルの絞り込み（見積先名・回答状態・初期表示条件）を効かせる区分。
        // **自区分だけに効かせる**。逆区分は「表示のみ」なので絞り込まずそのまま並べる。
        // 項目・案件を出すかどうかも自区分の絞り込み結果で決める（自区分にヒットが無ければ項目ごと非表示）。
        // 自区分が一覧に出ない（kind が逆区分のみ）ときだけ、表示している側に効かせる。
        $filterRelation = in_array($ownRelation, $relations, true) ? $ownRelation : ($relations[0] ?? null);
        $isQuoteRequest = $mode === 'quote-request'; // 見積依頼は移行済み（source_id あり）のみ対象
        $operable = self::MODE_OPERABLE_STATUS[$mode] ?? null;
        $empty = $operable === null;                                 // 未知の mode は空
        // 一覧を操作対象のステータスだけに絞る画面か（部長取消承認のみ true）。
        $hidesOther = in_array($mode, self::MODE_HIDES_OTHER_STATUS, true);

        // 見積先（t_payable_partners）の絞り込み：初期表示条件 ＋ 業者名 ＋（業者選定の）回答状態。
        $makeFilter = fn (bool $isBilling): callable => function (Builder $q) use ($operable, $hidesOther, $vendor, $answer, $mode, $isQuoteRequest, $isBilling): void {
            if ($hidesOther && $operable !== null) {
                $q->whereIn('approval_status', $operable);
            }
            if ($mode === 'vendor-selection' && ! $isBilling) {
                // 初期表示条件：業者側から見積回答されている（支払見積にデータがある）ものだけ。
                $q->whereHas('quotations');
            }
            if ($isQuoteRequest && ! $isBilling) {
                // 見積依頼：依頼可能な移行済み（source_id あり）の見積先を、未依頼・依頼済みともに並べる。
                // 未依頼／依頼済みの区別は送信回数（requests_count）で行い、画面側のトグルで絞り込む。
                // （送信は felix_total 経由のため、依頼可能なのは移行済み = source_id ありに限る）
                $q->whereNotNull('source_id');
            }
            if ($vendor !== false) {
                $q->whereHas('company', fn (Builder $c) => $c->where('company_name', 'like', "%{$vendor}%"));
            }
            if (($mode === 'vendor-selection' || $mode === 'quote-request') && ! $isBilling) {
                // 回答あり＝相見積額（最新の相見積履歴 is_latest）を持つ。回答なし＝持たない。
                $hasLatest = fn (Builder $h) => $h->whereNotNull('is_latest');
                if ($answer === 'answered') {
                    $q->whereHas('quotations', $hasLatest);
                } elseif ($answer === 'unanswered') {
                    $q->whereDoesntHave('quotations', $hasLatest);
                }
            }
        };
        // リレーション名 → 絞り込みクロージャ。自区分以外は素通し（表示のみ）。
        $passThrough = static function (Builder $q): void {};
        $filterFor = [
            'payablePartners' => $filterRelation === 'payablePartners' ? $makeFilter(false) : $passThrough,
            'billingPartners' => $filterRelation === 'billingPartners' ? $makeFilter(true) : $passThrough,
        ];

        $paginator = TBuilding::query()
            ->when($empty, fn (Builder $q) => $q->whereRaw('1 = 0'))
            ->when($keyword, fn (Builder $q, string $kw) => $q->where('name', 'like', "%{$kw}%"))
            ->whereHas('budgetItems', fn (Builder $i) => $this->applyItemFilter($i, $itemLabel, $comment, $filterFor, $filterRelation))
            ->with(['budgetItems' => function (HasMany $i) use ($itemLabel, $comment, $filterFor, $isQuoteRequest, $relations, $filterRelation): void {
                $this->applyItemFilter($i->getQuery(), $itemLabel, $comment, $filterFor, $filterRelation);
                $i->orderBy('sort')->orderBy('id');
                foreach ($relations as $relation) {
                    $filter = $filterFor[$relation];
                    $i->with([$relation => function (HasMany $q) use ($filter, $isQuoteRequest, $relation): void {
                        $filter($q->getQuery());
                        $q->orderBy('id')->with(['company:id,company_name', 'latestQuotation']);
                        // 見積依頼：送信回数（requests_count）を一覧表示・未依頼判定に使う。
                        // 併せて最終依頼日時（requests_max_requested_at＝requested_at の最大値）も付与する。
                        // 依頼履歴は支払側にしか無いため、請求（もらい）では付けない。
                        if ($isQuoteRequest && $relation === 'payablePartners') {
                            $q->withCount('requests')->withMax('requests', 'requested_at');
                        }
                    }]);
                }
            }])
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        // 表示用の見積額を各見積先に付与：
        // 見積依頼 / 業者選定＝相見積（業者の最新回答＝最新履歴）/ それ以外＝確定見積（項目）。
        $useLatestQuote = in_array($mode, ['quote-request', 'vendor-selection'], true);
        // 確定見積を出す画面（部長承認 / 取消申請 / 取消承認）の項目 ID → 金額。
        $settledQuotes = $useLatestQuote ? [] : $this->settledQuoteMap($paginator);
        // 操作できるのは自区分（支払）の取引先だけ（$screenIsBilling は上で定義）。
        foreach ($paginator as $building) {
            foreach ($building->budgetItems as $item) {
                // 確定見積＝選定済みの見積先の最新見積額（amount_excluding_tax。§settledQuoteMap）。
                $settledQuote = $settledQuotes[(int) $item->id] ?? null;
                // Resource が区分に依らず同じ名前で読めるよう、表示対象を displayPartners に寄せる。
                // all のときは支払と請求を1つの一覧に合成する（請求＝もらいを先に並べる）。
                $partners = collect();
                foreach ($relations as $relation) {
                    $isBilling = $relation === 'billingPartners';
                    foreach ($item->getRelation($relation) as $quotation) {
                        $quotation->setAttribute('display_quote', $useLatestQuote
                            ? optional($quotation->latestQuotation)->amount_excluding_tax
                            : $settledQuote);
                        // 区分（請求＝もらい / 支払＝はらい）。行の地色・バッジに使う。
                        $quotation->setAttribute('billing_target', $isBilling);
                        // 操作できる行か（処理フロー J列）。false の行は一覧に出すが操作させない（K列）。
                        // **画面の区分と異なる取引先は常に表示のみ**（操作させない）。
                        $quotation->setAttribute('operable', $isBilling === $screenIsBilling
                            && in_array((string) $quotation->approval_status, $operable ?? [], true));
                        $partners->push($quotation);
                    }
                }
                $item->setRelation('displayPartners', $partners);
            }
        }

        // やり取り（コメント）のメタ情報（件数・コメント有無・未読数）を各見積先に付与（全画面）。
        // コメントは建物予算項目（t_building_budget_items）単位。未読はログインユーザーの最終既読より新しい他者コメント。
        $this->attachCommentMeta($paginator);

        return $paginator;
    }

    /**
     * 確定見積（項目 ID → 税抜金額）を求める。
     *
     * 確定見積＝**選定済み（`approval_status <> 'DRAFT'`）の支払見積先の最新見積額**
     * （`t_payable_quotations` の `is_latest` の `amount_excluding_tax`）。
     * 相見積・確定見積・見積額はいずれも対応テーブルの `amount_excluding_tax` を出す方針に揃える。
     *
     * `t_building_budget_items.quotation_amount` は使わない。同列は felix_total の実行予算画面で
     * 保存し直したときにだけ同期される（丸めた）コピーで、fix 側の選定確定では更新されないため、
     * 未同期のまま空になったり、未選定の項目に古い値が残ったりして実データとズレる。
     *
     * 一覧の絞り込み（見積先名・ステータス）で選定済みの行が表示対象から外れていても拾えるよう、
     * 表示中の項目 ID から引き直す。同一項目に選定済みが複数あるときは最小 ID の行を採用する
     * （業者選定は1業者が前提。旧 felix_total の `adoption_flg=1` に相当）。
     *
     * @param  LengthAwarePaginator<int, TBuilding>  $paginator
     * @return array<int, int> 項目 ID → 税抜金額（選定済みが無い項目は含めない＝画面は「—」）
     */
    private function settledQuoteMap(LengthAwarePaginator $paginator): array
    {
        $itemIds = [];
        foreach ($paginator as $building) {
            foreach ($building->budgetItems as $item) {
                $itemIds[(int) $item->id] = true;
            }
        }
        $itemIds = array_keys($itemIds);

        if ($itemIds === []) {
            return [];
        }

        $rows = DB::table('t_payable_partners as p')
            ->join('t_payable_quotations as q', function ($join): void {
                $join->on('q.payable_partner_id', '=', 'p.id')
                    ->where('q.is_latest', true)
                    ->whereNull('q.deleted_at');
            })
            ->whereIn('p.building_budget_item_id', $itemIds)
            ->where('p.approval_status', '<>', 'DRAFT')
            ->whereNull('p.deleted_at')
            ->orderBy('p.id')
            ->get(['p.building_budget_item_id as item_id', 'q.amount_excluding_tax as amount']);

        $map = [];
        foreach ($rows as $row) {
            // orderBy('p.id') のため、最初に現れた（＝最小 ID の）選定済み見積先の額を採用する。
            $map[(int) $row->item_id] ??= (int) $row->amount;
        }

        return $map;
    }

    /**
     * ページ内の各見積先に、コメント（t_comments）のメタ情報を付与する。
     * コメントは建物予算項目（t_building_budget_items）単位のポリモーフィック（commentable）で保持する。
     *
     * 付与する属性（同一項目の各見積先へ同じ値を割り当てる。ボタンは項目の先頭見積先に出るため）:
     * - comments_count : 項目のコメント総数（「やり取り」列・messageCount 用）
     * - has_comments   : コメントが1件以上あるか（コメントボタンの配色用）
     * - unread_count   : ログインユーザーの最終既読（t_comment_read_timestamps）より新しい他者コメント数
     * - denied         : 「【否認】」で始まるコメントがあるか（否認差し戻しの赤色表示用）
     *
     * @param  LengthAwarePaginator<int, TBuilding>  $paginator
     */
    private function attachCommentMeta(LengthAwarePaginator $paginator): void
    {
        // コメント対象＝費用項目のモーフ型（morph map 未設定のため FQCN）。
        $morphType = (new TBuildingBudgetItem)->getMorphClass();

        $admin = Auth::guard('admin')->user();
        $userId = $admin instanceof AdminUser ? (int) $admin->id : 0;

        $quotations = [];
        $itemIds = [];
        foreach ($paginator as $building) {
            foreach ($building->budgetItems as $item) {
                $itemIds[(int) $item->id] = true;
                foreach ($item->displayPartners as $quotation) {
                    $quotations[] = $quotation;
                }
            }
        }
        $itemIds = array_keys($itemIds);

        $countMap = [];
        $unreadMap = [];
        $deniedMap = [];
        if ($itemIds !== []) {
            // 項目ごとのコメント総数。
            $countMap = DB::table('t_comments')
                ->where('commentable_type', $morphType)
                ->whereIn('commentable_id', $itemIds)
                ->groupBy('commentable_id')
                ->selectRaw('commentable_id as id, COUNT(*) as cnt')
                ->pluck('cnt', 'id')
                ->all();

            // 否認済み判定（新スキーマに否認理由の列が無いため、コメント本文の接頭辞で判定する）。
            $deniedMap = array_flip(array_map('intval', DB::table('t_comments')
                ->where('commentable_type', $morphType)
                ->whereIn('commentable_id', $itemIds)
                ->where('body', 'like', '【否認】%')
                ->distinct()
                ->pluck('commentable_id')
                ->all()));

            // 項目ごとの未読数（最終既読より後・かつ他者の投稿）。
            $unreadMap = DB::table('t_comments as c')
                ->leftJoin('t_comment_read_timestamps as r', function ($join) use ($morphType, $userId): void {
                    $join->on('r.readable_id', '=', 'c.commentable_id')
                        ->where('r.readable_type', '=', $morphType)
                        ->where('r.user_id', '=', $userId);
                })
                ->where('c.commentable_type', $morphType)
                ->whereIn('c.commentable_id', $itemIds)
                ->where('c.user_id', '!=', $userId)
                ->whereRaw('(r.last_read_at IS NULL OR c.created_at > r.last_read_at)')
                ->groupBy('c.commentable_id')
                ->selectRaw('c.commentable_id as id, COUNT(*) as cnt')
                ->pluck('cnt', 'id')
                ->all();
        }

        foreach ($quotations as $quotation) {
            $itemId = (int) $quotation->building_budget_item_id;
            $count = (int) ($countMap[$itemId] ?? 0);
            $quotation->setAttribute('comments_count', $count);
            $quotation->setAttribute('has_comments', $count > 0);
            $quotation->setAttribute('unread_count', (int) ($unreadMap[$itemId] ?? 0));
            $quotation->setAttribute('denied', isset($deniedMap[$itemId]));
        }
    }

    /**
     * 項目（明細）の絞り込み：項目名 ＋ コメント有無 ＋ 自区分の取引先を持つこと。
     *
     * @param  array<string, callable>  $filterFor  リレーション名 → 絞り込みクロージャ
     * @param  string|null  $filterRelation  絞り込みを効かせる（＝項目の存在判定に使う）自区分のリレーション。
     *                                       逆区分は表示のみのため判定に使わない（共通仕様 §3.3）。
     */
    private function applyItemFilter(Builder $i, string|false $itemLabel, string $comment, array $filterFor, ?string $filterRelation): Builder
    {
        if ($itemLabel !== false) {
            $i->where('name', 'like', "%{$itemLabel}%");
        }

        // コメント有無（コメントは項目=t_building_budget_items 単位のポリモーフィック）。
        if ($comment === 'has') {
            $i->whereHas('comments');
        } elseif ($comment === 'none') {
            $i->whereDoesntHave('comments');
        }

        // 項目を出すかどうかは**自区分**（$filterRelation）に絞り込み後の取引先が残るかで決める。
        // 逆区分は表示のみ（絞り込み対象外）のため、ここでの判定には使わない。
        if ($filterRelation === null) {
            return $i;
        }

        $filter = $filterFor[$filterRelation];

        return $i->whereHas($filterRelation, fn (Builder $p) => $filter($p));
    }

    /**
     * 見積依頼送信。チェックされた見積先（t_payable_partners）を旧 ID（source_id）へ写像し、
     * felix_total の見積依頼処理（トークン発行＋履歴作成＋業者へメール送信）をサーバ間 HTTP で実行する。
     *
     * @param  list<int>  $companyIds  見積先（t_payable_partners.id）の配列
     * @return int 依頼を送信した見積先の件数
     */
    public function recordQuoteRequests(array $companyIds): int
    {
        if ($companyIds === []) {
            return 0;
        }

        // 新スキーマの見積先 → 旧 ID（source_id）対応を取得する。
        // felix_total は "{estimate_units.id}:{estimate_unit_companies.id}" 形式を要求するため、
        // 見積先の source_id（= 旧 estimate_unit_companies.id）と
        // 費用 t_building_budget_items.source_id（= 旧 estimate_units.id）の両方が要る。
        $quotations = TPayablePartner::query()
            ->whereIn('id', $companyIds)
            ->whereNotNull('source_id')
            ->with(['budgetItem:id,source_id'])
            ->get(['id', 'building_budget_item_id', 'source_id']);

        $pairs = [];
        foreach ($quotations as $quotation) {
            $unitSourceId = $quotation->budgetItem?->source_id;
            if ($unitSourceId === null || $quotation->source_id === null) {
                continue; // 移行元の無い見積先は felix_total へ依頼できないため除外。
            }
            $pairs[] = "{$unitSourceId}:{$quotation->source_id}";
        }

        if ($pairs === []) {
            return 0;
        }

        return $this->felix->orderEstimate($pairs);
    }

    /**
     * 発注業者選定の確定：新テーブルの承認状態を DRAFT → APPLIED へ進めたうえで、
     * felix_total の採用（update_adoption_flg）をサーバ間 HTTP で呼ぶ。
     *
     * @param  list<int>  $companyIds  見積先（t_payable_partners.id）
     * @return int DRAFT → APPLIED へ実際に遷移した見積先の件数
     */
    public function recordVendorSelections(array $companyIds): int
    {
        return $this->syncWithFelixTotal(
            $companyIds,
            'DRAFT',
            'APPLIED',
            // no_competitive_flg は現行 estimate_units に列が無いため 0（相見積あり＝単一採用）で渡す。
            fn (int $unit, int $company) => $this->felix->adoptCompany($unit, $company),
        );
    }

    /**
     * 部長承認：新テーブルの承認状態を APPLIED → APPROVED へ進めたうえで、
     * felix_total の建設部選定（update_tmp_company_select_flg）をサーバ間 HTTP で呼ぶ。
     *
     * @param  list<int>  $companyIds  見積先（t_payable_partners.id）
     * @return int APPLIED → APPROVED へ実際に遷移した見積先の件数
     */
    public function recordManagerApprovals(array $companyIds): int
    {
        return $this->syncWithFelixTotal(
            $companyIds,
            'APPLIED',
            'APPROVED',
            fn (int $unit, int $company) => $this->felix->tmpSelectCompany($unit, $company),
        );
    }

    /**
     * 「新テーブルを先に更新 → 現行 felix_total の処理を呼ぶ」を1トランザクションで行う共通処理。
     *
     * 承認状態が from の見積先だけを to へ進め、実際に遷移した行に対してのみ felix_total を呼ぶ。
     * felix_total 側が失敗（接続不可・非 2xx・権限エラー）した場合は例外が送出され、
     * 新テーブルの更新はロールバックされる（＝両方書けたときだけ成功として扱う）。
     *
     * @param  list<int>  $companyIds  見積先（t_payable_partners.id）
     * @param  string  $from  遷移元の承認状態
     * @param  string  $to  遷移先の承認状態
     * @param  callable(int, int): void  $callFelixTotal  旧 ID（unit, company）を受け取り現行処理を呼ぶ
     * @return int 実際に遷移した見積先の件数
     */
    private function syncWithFelixTotal(array $companyIds, string $from, string $to, callable $callFelixTotal): int
    {
        // 移行元（source_id）を持つ見積先だけが felix_total 連携の対象。
        $rows = $this->mapSourceIds($companyIds);
        if ($rows === []) {
            return 0;
        }

        // 遷移元の状態にある行だけを対象にする（二重送信・状態不整合の防止）。
        $targets = $this->filterByStatus($rows, $from);
        if ($targets === []) {
            return 0;
        }

        return DB::transaction(function () use ($targets, $from, $to, $callFelixTotal): int {
            $count = $this->advanceStatus(array_column($targets, 'id'), $from, $to);
            if ($count === 0) {
                return 0;
            }

            // 現行処理の呼び出しで例外が出た場合はトランザクションごとロールバックされる。
            foreach ($targets as $target) {
                $callFelixTotal($target['unit'], $target['company']);
            }

            return $count;
        });
    }

    /**
     * 見積先のうち、承認状態が $status のものだけに絞り込む。
     *
     * @param  list<array{id:int, unit:int, company:int}>  $rows
     * @return list<array{id:int, unit:int, company:int}>
     */
    private function filterByStatus(array $rows, string $status): array
    {
        $matched = TPayablePartner::query()
            ->whereIn('id', array_column($rows, 'id'))
            ->where('approval_status', $status)
            ->pluck('id')
            ->all();

        $allowed = array_flip(array_map('intval', $matched));

        return array_values(array_filter($rows, fn (array $row) => isset($allowed[$row['id']])));
    }

    /**
     * 部長承認の否認（業者選定へ差し戻し）：担当承認済（APPLIED）→ 未選定（DRAFT）。
     * felix_total の採用取消（update_adoption_flg / mode=false）
     * をサーバ間 HTTP で呼び、現行側も業者選定前の状態（adoption_flg=0）へ戻す。
     *
     * 選定・承認（{@see syncWithFelixTotal}）と同様、felix_total 側が失敗した場合は
     * 新テーブルの更新をロールバックする。APPLIED 以外は更新しない。
     *
     * @return int 実際に差し戻した件数
     */
    public function rejectManagerApproval(int $companyId, string $reason): int
    {
        // 移行元（source_id）が無い見積先は felix_total を呼べないため、新テーブルのみ差し戻す。
        $targets = $this->filterByStatus($this->mapSourceIds([$companyId]), 'APPLIED');

        return DB::transaction(function () use ($companyId, $targets): int {
            $count = TPayablePartner::query()
                ->where('id', $companyId)
                ->where('approval_status', 'APPLIED')
                // 一括更新はモデルイベントが発火しないため、更新者（updated_by）を明示的に押印する。
                // 否認理由は新スキーマに列が無いため、項目単位のコメントスレッド
                // （t_comments に「【否認】{理由}」で投稿）を唯一の記録とする。投稿は Service 側で行う。
                ->update(Blame::stampUpdate(['approval_status' => 'DRAFT']));

            if ($count === 0) {
                return 0;
            }

            foreach ($targets as $target) {
                $this->felix->cancelAdoption($target['unit'], $target['company']);
            }

            return $count;
        });
    }

    /**
     * 部長取消承認の否認（取消申請の却下）：取消申請中（CANCEL_APPLIED）→ 部長承認済（APPROVED）。
     *
     * 取消申請（{@see recordCancelRequests}）は felix_total を呼ばず新テーブルの状態を進めるだけなので、
     * その却下である本処理も新テーブルの状態を戻すだけでよい（現行側は承認済みのまま触らない）。
     * 否認理由は新スキーマに列が無いため、項目単位のコメントスレッド
     * （t_comments に「【否認】{理由}」で投稿）を唯一の記録とする。投稿は Service 側で行う。
     *
     * @return int 実際に却下した件数（0=対象外）
     */
    public function rejectCancelApproval(int $companyId, string $reason): int
    {
        return $this->advanceStatus([$companyId], 'CANCEL_APPLIED', 'APPROVED');
    }

    /**
     * 見積先（t_payable_partners.id）が属する建物予算項目（t_building_budget_items.id）を返す。
     */
    public function itemIdForQuotation(int $quotationId): ?int
    {
        $itemId = TPayablePartner::query()
            ->where('id', $quotationId)
            ->value('building_budget_item_id');

        return $itemId === null ? null : (int) $itemId;
    }

    /**
     * 部長取消申請：部長承認済 → 取消申請中。
     *
     * @param  list<int>  $companyIds
     */
    public function recordCancelRequests(array $companyIds): int
    {
        return $this->advanceStatus($companyIds, 'APPROVED', 'CANCEL_APPLIED');
    }

    /**
     * 部長取消承認：取消申請中 → 設計部承認済（取消承認＝完了扱い）。
     *
     * 選定・承認（{@see syncWithFelixTotal}）と同じく「新テーブルを更新 → 現行 API」の順で行い、
     * 現行 felix_total 側の業者選定状態を外す：
     *   1. 建設部選定の取消（update_tmp_company_select_flg / mode=false）
     *      … estimate_units.tmp_company_id=NULL / company_select_status=1 / tmp_status=NULL
     *   2. 採用の取消（update_adoption_flg / mode=false）
     *      … adoption_flg=0 / company_select_flg=NULL / estimate_units.vendor_id=NULL・price 再計算
     * 取消承認は部長承認済み（tmp 選定済み）からの遷移のため、採用だけでなく建設部選定も外さないと
     * estimate_units.tmp_company_id が採用外の業者を指したまま残る。
     *
     * felix_total 側が失敗した場合は新テーブルの更新をロールバックする。
     *
     * @param  list<int>  $companyIds
     */
    public function recordCancelApprovals(array $companyIds): int
    {
        return $this->syncWithFelixTotal(
            $companyIds,
            'CANCEL_APPLIED',
            'CANCELLED',
            function (int $unit, int $company): void {
                // 承認の段を逆順に戻す（建設部選定 → 採用の順で解除）。
                $this->felix->cancelTmpSelection($unit, $company);
                $this->felix->cancelAdoption($unit, $company);
            },
        );
    }

    /**
     * 見積先（t_payable_partners.id）を旧 ID（source_id）へ写像する。移行元の無い行は除外。
     *
     * @param  list<int>  $companyIds
     * @return list<array{id:int, unit:int, company:int}> id=t_payable_partners.id / unit=旧 estimate_units.id / company=旧 estimate_unit_companies.id
     */
    private function mapSourceIds(array $companyIds): array
    {
        if ($companyIds === []) {
            return [];
        }

        $quotations = TPayablePartner::query()
            ->whereIn('id', $companyIds)
            ->whereNotNull('source_id')
            ->with(['budgetItem:id,source_id'])
            ->get(['id', 'building_budget_item_id', 'source_id']);

        $rows = [];
        foreach ($quotations as $quotation) {
            $unitSourceId = $quotation->budgetItem?->source_id;
            if ($unitSourceId === null || $quotation->source_id === null) {
                continue;
            }
            $rows[] = ['id' => (int) $quotation->id, 'unit' => (int) $unitSourceId, 'company' => (int) $quotation->source_id];
        }

        return $rows;
    }

    /**
     * 指定の見積先の approval_status を from → to に進める（実際に from だった件数を返す）。
     *
     * @param  list<int>  $ids
     */
    private function advanceStatus(array $ids, string $from, string $to): int
    {
        if ($ids === []) {
            return 0;
        }

        return TPayablePartner::query()
            ->whereIn('id', $ids)
            ->where('approval_status', $from)
            // 一括更新はモデルイベントが発火しないため、更新者（updated_by）を明示的に押印する。
            ->update(Blame::stampUpdate(['approval_status' => $to]));
    }

    /**
     * 仮選定の保存：t_payable_partners.is_drafted を更新する。
     *
     * @param  int  $companyId  t_payable_partners.id
     */
    public function setProvisional(int $companyId, bool $drafted): int
    {
        return TPayablePartner::query()
            ->where('id', $companyId)
            // 一括更新はモデルイベントが発火しないため、更新者（updated_by）を明示的に押印する。
            ->update(Blame::stampUpdate(['is_drafted' => $drafted ? 1 : 0]));
    }

    public function pendingCounts(): array
    {
        return [
            // 見積依頼：移行済み（source_id あり）かつ費用見積依頼（t_payable_quotation_requests）が無い見積先。
            'quote-request' => TPayablePartner::query()
                ->whereNotNull('source_id')
                ->whereNotExists(fn (QueryBuilder $sub) => $sub->selectRaw('1')
                    ->from('t_payable_quotation_requests')
                    ->whereColumn('t_payable_quotation_requests.payable_partner_id', 't_payable_partners.id'))
                ->count(),
            // 業者選定：未選定（DRAFT）かつ業者回答あり（最新の相見積履歴を持つ）。
            'vendor-selection' => TPayablePartner::query()
                ->where('approval_status', 'DRAFT')
                ->whereHas('quotations', fn (Builder $h) => $h->whereNotNull('is_latest'))
                ->count(),
            // 業者選定（差し戻し）：部長承認で否認され業者選定へ戻った。
            // 新スキーマに否認理由の列が無いため、項目のコメントに「【否認】」で始まる投稿が
            // あることをもって否認済みと判定する（{@see denialItemIds}）。
            'vendor-selection-rejected' => TPayablePartner::query()
                ->where('approval_status', 'DRAFT')
                ->whereIn('building_budget_item_id', $this->denialItemIds())
                ->count(),
            // 部長承認：担当承認済（APPLIED）で部長承認待ち。
            'manager-approval' => TPayablePartner::query()
                ->where('approval_status', 'APPLIED')
                ->count(),
            // 部長取消承認：取消申請中（CANCEL_APPLIED）で承認待ち。
            'cancel-approval' => TPayablePartner::query()
                ->where('approval_status', 'CANCEL_APPLIED')
                ->count(),
        ];
    }

    /**
     * 否認済み（部長承認で差し戻された）とみなす項目 ID の一覧。
     *
     * 2026-08 のスキーマ改訂で否認理由の列（旧 t_payable_partners.deny_comment）が無くなったため、
     * 項目単位のコメントスレッドに「【否認】」で始まる投稿があることをもって否認済みと判定する。
     * コメントは項目単位のため、同一項目に複数の見積先がある場合は全て否認扱いになる
     * （見積先単位の否認履歴が必要になったら t_approval_actions への記録を検討すること）。
     *
     * @return list<int>
     */
    private function denialItemIds(): array
    {
        return DB::table('t_comments')
            ->where('commentable_type', (new TBuildingBudgetItem)->getMorphClass())
            ->where('body', 'like', '【否認】%')
            ->distinct()
            ->pluck('commentable_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /** 値が「未指定（null/空文字/'all'）」でなければ文字列として返す。 */
    private function nonEmpty(mixed $value): string|false
    {
        if ($value === null || $value === '' || $value === 'all') {
            return false;
        }

        return (string) $value;
    }
}
