<?php

namespace App\Repositories;

use App\Models\AdminUser;
use App\Models\TBuilding;
use App\Models\TBuildingCostItem;
use App\Models\TCostQuotation;
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
 * 新スキーマ（t_buildings / t_building_cost_items / t_cost_quotations /
 * t_cost_quotation_histories）からの見積管理データ取得。
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
    /** mode → 表示対象の approval_status。見積依頼（quote-request）は status 非依存で別途扱う。 */
    private const MODE_STATUS = [
        'vendor-selection' => 'UNSELECTED',       // 業者未選定
        'manager-approval' => 'STAFF_APPROVED',   // 担当承認済（部長承認待ち）
        'cancel-request' => 'MANAGER_APPROVED',   // 部長承認済
        'cancel-approval' => 'CANCEL_REQUESTED',  // 取消申請中
    ];

    public function __construct(
        private readonly FelixTotalQuoteRequestGateway $felix,
    ) {}

    public function forEstimateManagement(array $filters, int $perPage, string $mode): LengthAwarePaginator
    {
        $keyword = $this->nonEmpty($filters['keyword'] ?? null);     // 実行予算名（building_name）
        $itemLabel = $this->nonEmpty($filters['itemLabel'] ?? null); // 項目名（item_name）
        $vendor = $this->nonEmpty($filters['vendor'] ?? null);       // 見積先（company_name）
        $answer = (string) ($filters['answer'] ?? 'all');            // 業者選定の回答状態（既定=全て）
        $comment = (string) ($filters['comment'] ?? 'all');          // コメント有無（all/has/none）
        $isQuoteRequest = $mode === 'quote-request';                 // 見積依頼は status 非依存
        $status = self::MODE_STATUS[$mode] ?? null;
        $empty = $status === null && ! $isQuoteRequest;              // 見積依頼以外で条件未定は空

        // 見積先（t_cost_quotations）の絞り込み：approval_status ＋ 業者名 ＋（業者選定の）回答状態。
        $quotationFilter = function (Builder $q) use ($status, $vendor, $answer, $mode, $isQuoteRequest): void {
            if ($status !== null) {
                $q->where('approval_status', $status);
            }
            if ($isQuoteRequest) {
                // 見積依頼：依頼可能な移行済み（source_id あり）の見積先を、未依頼・依頼済みともに並べる。
                // 未依頼／依頼済みの区別は送信回数（requests_count）で行い、画面側のトグルで絞り込む。
                // （送信は felix_total 経由のため、依頼可能なのは移行済み = source_id ありに限る）
                $q->whereNotNull('source_id');
            }
            if ($vendor !== false) {
                $q->whereHas('company', fn (Builder $c) => $c->where('company_name', 'like', "%{$vendor}%"));
            }
            if ($mode === 'vendor-selection' || $mode === 'quote-request') {
                // 回答あり＝相見積額（最新の相見積履歴 is_latest）を持つ。回答なし＝持たない。
                $hasLatest = fn (Builder $h) => $h->whereNotNull('is_latest');
                if ($answer === 'answered') {
                    $q->whereHas('histories', $hasLatest);
                } elseif ($answer === 'unanswered') {
                    $q->whereDoesntHave('histories', $hasLatest);
                }
            }
        };

        $paginator = TBuilding::query()
            ->when($empty, fn (Builder $q) => $q->whereRaw('1 = 0'))
            ->when($keyword, fn (Builder $q, string $kw) => $q->where('building_name', 'like', "%{$kw}%"))
            ->whereHas('costItems', fn (Builder $i) => $this->applyItemFilter($i, $itemLabel, $comment, $quotationFilter))
            ->with(['costItems' => function (HasMany $i) use ($itemLabel, $comment, $quotationFilter, $isQuoteRequest): void {
                $this->applyItemFilter($i->getQuery(), $itemLabel, $comment, $quotationFilter);
                $i->orderBy('sort')->orderBy('id')
                    ->with(['quotations' => function (HasMany $q) use ($quotationFilter, $isQuoteRequest): void {
                        $quotationFilter($q->getQuery());
                        $q->orderBy('id')->with(['company:id,company_name', 'latestHistory']);
                        // 見積依頼：送信回数（requests_count）を一覧表示・未依頼判定に使う。
                        if ($isQuoteRequest) {
                            $q->withCount('requests');
                        }
                    }]);
            }])
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        // 表示用の見積額を各見積先に付与：
        // 見積依頼 / 業者選定＝相見積（業者の最新回答＝最新履歴）/ それ以外＝確定見積（項目）。
        $useLatestQuote = in_array($mode, ['quote-request', 'vendor-selection'], true);
        foreach ($paginator as $building) {
            foreach ($building->costItems as $item) {
                foreach ($item->quotations as $quotation) {
                    $quotation->setAttribute('display_quote', $useLatestQuote
                        ? optional($quotation->latestHistory)->amount_excluding_tax
                        : $item->quotation_amount);
                }
            }
        }

        // やり取り（コメント）のメタ情報（件数・コメント有無・未読数）を各見積先に付与（全画面）。
        // コメントは費用項目（t_building_cost_items）単位。未読はログインユーザーの最終既読より新しい他者コメント。
        $this->attachCommentMeta($paginator);

        return $paginator;
    }

    /**
     * ページ内の各見積先に、コメント（t_comments）のメタ情報を付与する。
     * コメントは費用項目（t_building_cost_items）単位のポリモーフィック（commentable）で保持する。
     *
     * 付与する属性（同一項目の各見積先へ同じ値を割り当てる。ボタンは項目の先頭見積先に出るため）:
     * - comments_count : 項目のコメント総数（「やり取り」列・messageCount 用）
     * - has_comments   : コメントが1件以上あるか（コメントボタンの配色用）
     * - unread_count   : ログインユーザーの最終既読（t_comment_read_timestamps）より新しい他者コメント数
     *
     * @param  LengthAwarePaginator<int, TBuilding>  $paginator
     */
    private function attachCommentMeta(LengthAwarePaginator $paginator): void
    {
        // コメント対象＝費用項目のモーフ型（morph map 未設定のため FQCN）。
        $morphType = (new TBuildingCostItem)->getMorphClass();

        $admin = Auth::guard('admin')->user();
        $userId = $admin instanceof AdminUser ? (int) $admin->id : 0;

        $quotations = [];
        $itemIds = [];
        foreach ($paginator as $building) {
            foreach ($building->costItems as $item) {
                $itemIds[(int) $item->id] = true;
                foreach ($item->quotations as $quotation) {
                    $quotations[] = $quotation;
                }
            }
        }
        $itemIds = array_keys($itemIds);

        $countMap = [];
        $unreadMap = [];
        if ($itemIds !== []) {
            // 項目ごとのコメント総数。
            $countMap = DB::table('t_comments')
                ->where('commentable_type', $morphType)
                ->whereIn('commentable_id', $itemIds)
                ->groupBy('commentable_id')
                ->selectRaw('commentable_id as id, COUNT(*) as cnt')
                ->pluck('cnt', 'id')
                ->all();

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
            $itemId = (int) $quotation->building_cost_item_id;
            $count = (int) ($countMap[$itemId] ?? 0);
            $quotation->setAttribute('comments_count', $count);
            $quotation->setAttribute('has_comments', $count > 0);
            $quotation->setAttribute('unread_count', (int) ($unreadMap[$itemId] ?? 0));
        }
    }

    /** 項目（明細）の絞り込み：項目名 ＋ コメント有無 ＋ mode に合う見積先を持つこと。 */
    private function applyItemFilter(Builder $i, string|false $itemLabel, string $comment, callable $quotationFilter): Builder
    {
        if ($itemLabel !== false) {
            $i->where('item_name', 'like', "%{$itemLabel}%");
        }

        // コメント有無（コメントは項目=t_building_cost_items 単位のポリモーフィック）。
        if ($comment === 'has') {
            $i->whereHas('comments');
        } elseif ($comment === 'none') {
            $i->whereDoesntHave('comments');
        }

        return $i->whereHas('quotations', fn (Builder $q) => $quotationFilter($q));
    }

    /**
     * 見積依頼送信。チェックされた見積先（t_cost_quotations）を旧 ID（source_id）へ写像し、
     * felix_total の見積依頼処理（トークン発行＋履歴作成＋業者へメール送信）をサーバ間 HTTP で実行する。
     *
     * @param  list<int>  $companyIds  見積先（t_cost_quotations.id）の配列
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
        // 費用 t_building_cost_items.source_id（= 旧 estimate_units.id）の両方が要る。
        $quotations = TCostQuotation::query()
            ->whereIn('id', $companyIds)
            ->whereNotNull('source_id')
            ->with(['costItem:id,source_id'])
            ->get(['id', 'building_cost_item_id', 'source_id']);

        $pairs = [];
        foreach ($quotations as $quotation) {
            $unitSourceId = $quotation->costItem?->source_id;
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
     * 発注業者選定の確定：新テーブルの承認状態を UNSELECTED → STAFF_APPROVED へ進めたうえで、
     * felix_total の採用（update_adoption_flg）をサーバ間 HTTP で呼ぶ。
     *
     * @param  list<int>  $companyIds  見積先（t_cost_quotations.id）
     * @return int UNSELECTED → STAFF_APPROVED へ実際に遷移した見積先の件数
     */
    public function recordVendorSelections(array $companyIds): int
    {
        return $this->syncWithFelixTotal(
            $companyIds,
            'UNSELECTED',
            'STAFF_APPROVED',
            // no_competitive_flg は現行 estimate_units に列が無いため 0（相見積あり＝単一採用）で渡す。
            fn (int $unit, int $company) => $this->felix->adoptCompany($unit, $company),
        );
    }

    /**
     * 部長承認：新テーブルの承認状態を STAFF_APPROVED → MANAGER_APPROVED へ進めたうえで、
     * felix_total の建設部選定（update_tmp_company_select_flg）をサーバ間 HTTP で呼ぶ。
     *
     * @param  list<int>  $companyIds  見積先（t_cost_quotations.id）
     * @return int STAFF_APPROVED → MANAGER_APPROVED へ実際に遷移した見積先の件数
     */
    public function recordManagerApprovals(array $companyIds): int
    {
        return $this->syncWithFelixTotal(
            $companyIds,
            'STAFF_APPROVED',
            'MANAGER_APPROVED',
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
     * @param  list<int>  $companyIds  見積先（t_cost_quotations.id）
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
        $matched = TCostQuotation::query()
            ->whereIn('id', array_column($rows, 'id'))
            ->where('approval_status', $status)
            ->pluck('id')
            ->all();

        $allowed = array_flip(array_map('intval', $matched));

        return array_values(array_filter($rows, fn (array $row) => isset($allowed[$row['id']])));
    }

    /**
     * 部長承認の否認（業者選定へ差し戻し）：担当承認済（STAFF_APPROVED）→ 未選定（UNSELECTED）。
     * 否認理由（deny_comment）を記録したうえで、felix_total の採用取消（update_adoption_flg / mode=false）
     * をサーバ間 HTTP で呼び、現行側も業者選定前の状態（adoption_flg=0）へ戻す。
     *
     * 選定・承認（{@see syncWithFelixTotal}）と同様、felix_total 側が失敗した場合は
     * 新テーブルの更新をロールバックする。STAFF_APPROVED 以外は更新しない。
     *
     * @return int 実際に差し戻した件数
     */
    public function rejectManagerApproval(int $companyId, string $reason): int
    {
        // 移行元（source_id）が無い見積先は felix_total を呼べないため、新テーブルのみ差し戻す。
        $targets = $this->filterByStatus($this->mapSourceIds([$companyId]), 'STAFF_APPROVED');

        return DB::transaction(function () use ($companyId, $reason, $targets): int {
            $count = TCostQuotation::query()
                ->where('id', $companyId)
                ->where('approval_status', 'STAFF_APPROVED')
                // 一括更新はモデルイベントが発火しないため、更新者（updated_by）を明示的に押印する。
                ->update(Blame::stampUpdate([
                    'approval_status' => 'UNSELECTED',
                    'deny_comment' => $reason,
                ]));

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
     * 見積先（t_cost_quotations.id）が属する費用項目（t_building_cost_items.id）を返す。
     */
    public function itemIdForQuotation(int $quotationId): ?int
    {
        $itemId = TCostQuotation::query()
            ->where('id', $quotationId)
            ->value('building_cost_item_id');

        return $itemId === null ? null : (int) $itemId;
    }

    /**
     * 部長取消申請：部長承認済 → 取消申請中。
     *
     * @param  list<int>  $companyIds
     */
    public function recordCancelRequests(array $companyIds): int
    {
        return $this->advanceStatus($companyIds, 'MANAGER_APPROVED', 'CANCEL_REQUESTED');
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
            'CANCEL_REQUESTED',
            'APPROVED',
            function (int $unit, int $company): void {
                // 承認の段を逆順に戻す（建設部選定 → 採用の順で解除）。
                $this->felix->cancelTmpSelection($unit, $company);
                $this->felix->cancelAdoption($unit, $company);
            },
        );
    }

    /**
     * 見積先（t_cost_quotations.id）を旧 ID（source_id）へ写像する。移行元の無い行は除外。
     *
     * @param  list<int>  $companyIds
     * @return list<array{id:int, unit:int, company:int}> id=t_cost_quotations.id / unit=旧 estimate_units.id / company=旧 estimate_unit_companies.id
     */
    private function mapSourceIds(array $companyIds): array
    {
        if ($companyIds === []) {
            return [];
        }

        $quotations = TCostQuotation::query()
            ->whereIn('id', $companyIds)
            ->whereNotNull('source_id')
            ->with(['costItem:id,source_id'])
            ->get(['id', 'building_cost_item_id', 'source_id']);

        $rows = [];
        foreach ($quotations as $quotation) {
            $unitSourceId = $quotation->costItem?->source_id;
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

        return TCostQuotation::query()
            ->whereIn('id', $ids)
            ->where('approval_status', $from)
            // 一括更新はモデルイベントが発火しないため、更新者（updated_by）を明示的に押印する。
            ->update(Blame::stampUpdate(['approval_status' => $to]));
    }

    /**
     * 仮選定の保存：t_cost_quotations.is_drafted を更新する。
     *
     * @param  int  $companyId  t_cost_quotations.id
     */
    public function setProvisional(int $companyId, bool $drafted): int
    {
        return TCostQuotation::query()
            ->where('id', $companyId)
            // 一括更新はモデルイベントが発火しないため、更新者（updated_by）を明示的に押印する。
            ->update(Blame::stampUpdate(['is_drafted' => $drafted ? 1 : 0]));
    }

    public function pendingCounts(): array
    {
        return [
            // 見積依頼：移行済み（source_id あり）かつ費用見積依頼（t_cost_quotation_requests）が無い見積先。
            'quote-request' => TCostQuotation::query()
                ->whereNotNull('source_id')
                ->whereNotExists(fn (QueryBuilder $sub) => $sub->selectRaw('1')
                    ->from('t_cost_quotation_requests')
                    ->whereColumn('t_cost_quotation_requests.cost_quotation_id', 't_cost_quotations.id'))
                ->count(),
            // 業者選定：未選定（UNSELECTED）かつ業者回答あり（最新の相見積履歴を持つ）。
            'vendor-selection' => TCostQuotation::query()
                ->where('approval_status', 'UNSELECTED')
                ->whereHas('histories', fn (Builder $h) => $h->whereNotNull('is_latest'))
                ->count(),
            // 業者選定（差し戻し）：部長承認で否認され業者選定へ戻った（UNSELECTED かつ否認理由あり）。
            'vendor-selection-rejected' => TCostQuotation::query()
                ->where('approval_status', 'UNSELECTED')
                ->whereNotNull('deny_comment')
                ->count(),
            // 部長承認：担当承認済（STAFF_APPROVED）で部長承認待ち。
            'manager-approval' => TCostQuotation::query()
                ->where('approval_status', 'STAFF_APPROVED')
                ->count(),
            // 部長取消承認：取消申請中（CANCEL_REQUESTED）で承認待ち。
            'cancel-approval' => TCostQuotation::query()
                ->where('approval_status', 'CANCEL_REQUESTED')
                ->count(),
        ];
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
