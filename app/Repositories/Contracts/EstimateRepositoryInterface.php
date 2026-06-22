<?php

namespace App\Repositories\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface EstimateRepositoryInterface
{
    /**
     * 見積管理画面（申請/承認専用）用。● 物件の本見積ユニット + 見積先を、見積部分の
     * 表示に必要な範囲（単価・見積書ファイル・依頼履歴・採用フラグ）で読み込みページネーションする。
     *
     * @param  array<string, mixed>  $filters 正規化済みの絞り込み条件（keyword=物件名 / itemLabel=項目名）
     * @return LengthAwarePaginator<int, \App\Models\Estimate>
     */
    public function forEstimateManagement(array $filters, int $perPage): LengthAwarePaginator;

    /**
     * 見積依頼送信（相見積依頼の記録）。指定の見積先（EstimateUnitCompany.id）ごとに
     * `estimate_order_histories` を1件作成する。既に依頼履歴がある見積先は重複して作成しない。
     *
     * @param  list<int>  $companyIds 見積先（EstimateUnitCompany）の ID 配列
     * @return int 実際に依頼を記録した件数
     */
    public function recordQuoteRequests(array $companyIds): int;

    /**
     * 発注業者選定の確定。指定の見積先（EstimateUnitCompany.id）を採用（adoption_flg=1）し、
     * 同一ユニット内の他の見積先は採用解除（adoption_flg=0）して「項目ごとに選定済みの業者」を確定する。
     *
     * @param  list<int>  $companyIds 選定された見積先（EstimateUnitCompany）の ID 配列
     * @return int 実際に採用を確定した見積先の件数
     */
    public function recordVendorSelections(array $companyIds): int;

    /**
     * 設計部選定の確定。指定の見積先（EstimateUnitCompany.id）を設計部選定済み（design_status=1）にし、
     * 同一ユニット内の他の見積先は選定解除（design_status=0）して「項目ごとに設計部が選定した業者」を確定する。
     *
     * @param  list<int>  $companyIds 選定された見積先（EstimateUnitCompany）の ID 配列
     * @return int 実際に選定を確定した見積先の件数
     */
    public function recordDesignSelections(array $companyIds): int;

    /**
     * 部長承認。選定された見積先（EstimateUnitCompany）を承認済み（fix_status=1）にする。
     *
     * @param  list<int>  $companyIds 承認する見積先（EstimateUnitCompany）の ID 配列
     * @return int 実際に更新した件数
     */
    public function recordManagerApprovals(array $companyIds): int;

    /**
     * 部長取消申請。対象の見積先（EstimateUnitCompany）を取消申請中（cancel_flg=1）にする。
     *
     * @param  list<int>  $companyIds 取消申請する見積先（EstimateUnitCompany）の ID 配列
     * @return int 実際に更新した件数
     */
    public function recordCancelRequests(array $companyIds): int;

    /**
     * 部長取消承認。対象の見積先（EstimateUnitCompany）を取消承認済み（cancel_flg=2）にする。
     *
     * @param  list<int>  $companyIds 取消承認する見積先（EstimateUnitCompany）の ID 配列
     * @return int 実際に更新した件数
     */
    public function recordCancelApprovals(array $companyIds): int;
}
