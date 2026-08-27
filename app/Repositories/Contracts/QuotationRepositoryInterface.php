<?php

namespace App\Repositories\Contracts;

use App\Models\TBuilding;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface QuotationRepositoryInterface
{
    /**
     * 見積管理画面（申請/承認専用）用。● 物件の本見積ユニット + 見積先を、見積部分の
     * 表示に必要な範囲（単価・見積書ファイル・依頼履歴・採用フラグ）で読み込みページネーションする。
     *
     * @param  array<string, mixed>  $filters  正規化済みの絞り込み条件（keyword=物件名 / itemLabel=項目名）
     * @param  string  $mode  画面（業務ステージ）。見積先をステージに応じて絞り込む。
     * @return LengthAwarePaginator<int, TBuilding>
     */
    public function forEstimateManagement(array $filters, int $perPage, string $mode): LengthAwarePaginator;

    /**
     * 見積依頼送信（相見積依頼の記録）。指定の見積先（EstimateUnitCompany.id）ごとに
     * `estimate_order_histories` を1件作成する。既に依頼履歴がある見積先は重複して作成しない。
     *
     * @param  list<int>  $companyIds  見積先（EstimateUnitCompany）の ID 配列
     * @return int 実際に依頼を記録した件数
     */
    public function recordQuoteRequests(array $companyIds): int;

    /**
     * 業者選定の確定。指定の見積先（EstimateUnitCompany.id）を採用（adoption_flg=1）し、
     * 同一ユニット内の他の見積先は採用解除（adoption_flg=0）して「項目ごとに選定済みの業者」を確定する。
     *
     * @param  list<int>  $companyIds  選定された見積先（EstimateUnitCompany）の ID 配列
     * @return int 実際に採用を確定した見積先の件数
     */
    public function recordVendorSelections(array $companyIds): int;

    /**
     * 部長承認。選定された見積先（EstimateUnitCompany）を承認済み（fix_status=1）にする。
     *
     * @param  list<int>  $companyIds  承認する見積先（EstimateUnitCompany）の ID 配列
     * @return int 実際に更新した件数
     */
    public function recordManagerApprovals(array $companyIds): int;

    /**
     * 部長承認の否認（業者選定へ差し戻し）。対象の見積先（担当承認済＝APPLIED）を
     * 未選定（DRAFT）へ戻し、否認理由（deny_comment）を記録する。
     *
     * @param  int  $companyId  見積先 ID（新スキーマ=t_payable_partners.id）
     * @param  string  $reason  否認理由
     * @return int 実際に差し戻した件数（0=対象外）
     */
    public function rejectManagerApproval(int $companyId, string $reason): int;

    /**
     * 部長取消承認の否認（取消申請の却下）。取消を認めず、部長承認済み（APPROVED）のまま据え置く。
     *
     * @param  int  $companyId  見積先 ID（新スキーマ=t_payable_partners.id）
     * @param  string  $reason  否認理由（記録は項目のコメントスレッド。Service が投稿する）
     * @return int 実際に却下した件数（0=対象外）
     */
    public function rejectCancelApproval(int $companyId, string $reason): int;

    /**
     * 見積先（t_payable_partners.id）が属する建物予算項目（t_building_budget_items.id）を返す。
     * コメント（項目単位）を紐づける際の commentable_id 解決に使う。
     *
     * @param  int  $quotationId  見積先 ID（t_payable_partners.id）
     * @return int|null 費用項目 ID（見積先が無ければ null）
     */
    public function itemIdForQuotation(int $quotationId): ?int;

    /**
     * 部長取消申請。対象の見積先（EstimateUnitCompany）を取消申請中（cancel_flg=1）にする。
     *
     * @param  list<int>  $companyIds  取消申請する見積先（EstimateUnitCompany）の ID 配列
     * @return int 実際に更新した件数
     */
    public function recordCancelRequests(array $companyIds): int;

    /**
     * 部長取消承認。対象の見積先（EstimateUnitCompany）を取消承認済み（cancel_flg=2）にする。
     *
     * @param  list<int>  $companyIds  取消承認する見積先（EstimateUnitCompany）の ID 配列
     * @return int 実際に更新した件数
     */
    public function recordCancelApprovals(array $companyIds): int;

    /**
     * 仮選定の保存。指定の見積先を仮選定 ON/OFF にする。
     * 新スキーマ（t_payable_partners.is_drafted）でのみ永続化し、旧スキーマは未対応（0 を返す）。
     *
     * @param  int  $companyId  見積先 ID（新スキーマ=t_payable_partners.id）
     * @param  bool  $drafted  仮選定 ON=true / OFF=false
     * @return int 実際に更新した件数（0=未更新/未対応）
     */
    public function setProvisional(int $companyId, bool $drafted): int;

    /**
     * サイドメニューのバッヂ用：各画面の未処理件数（見積先=t_payable_partners 単位）。
     * - quote-request    : まだ見積依頼を出していない数（t_payable_quotation_requests が無い）
     * - vendor-selection : まだ選定していない数（DRAFT かつ業者回答あり）
     * - manager-approval : まだ部長承認していない数（APPLIED）
     * - cancel-approval  : 取消申請されており未承認の数（CANCEL_REQUESTED）
     *
     * @return array<string, int>
     */
    public function pendingCounts(): array;
}
