<?php

namespace App\Services;

use App\Models\Estimate;
use App\Repositories\Contracts\EstimateRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * 見積管理（申請/承認専用）画面のユースケース。
 *
 * felix_total 実行予算画面の「見積部分」のみを切り出した画面群（見積り依頼・
 * 発注業者選定・部長承認 …）に、共通の「案件 → 項目 → 見積先」データを提供する。
 * Controller → Service → Repository。
 */
class EstimateManagementService
{
    public function __construct(
        private readonly EstimateRepositoryInterface $estimates,
    ) {}

    /**
     * 見積管理画面用の案件一覧を取得する。
     *
     * @param  array<string, mixed>  $filters  正規化済みの絞り込み条件
     * @return LengthAwarePaginator<int, Estimate>
     */
    public function paginate(array $filters, int $perPage): LengthAwarePaginator
    {
        return $this->estimates->forEstimateManagement($filters, $perPage);
    }

    /**
     * 見積依頼送信。選択された見積先（EstimateUnitCompany）に相見積依頼を記録する。
     *
     * ※ 業者へのメール通知（felix_total のトークン発行＋送信）は本フェーズ未対応。
     *    本処理は依頼履歴の記録までを担い、一覧の「依頼済み」表示を確定させる。
     *
     * @param  list<int>  $companyIds 見積先（EstimateUnitCompany）の ID 配列
     * @return int 実際に依頼を記録した件数
     */
    public function sendQuoteRequests(array $companyIds): int
    {
        return $this->estimates->recordQuoteRequests($companyIds);
    }

    /**
     * 発注業者選定の確定。選択された見積先（EstimateUnitCompany）を採用業者として確定する。
     *
     * 項目ごとに採用業者は1社のため、選択を含むユニット内の他業者は採用解除される。
     *
     * @param  list<int>  $companyIds 選定された見積先（EstimateUnitCompany）の ID 配列
     * @return int 実際に採用を確定した見積先の件数
     */
    public function confirmVendorSelections(array $companyIds): int
    {
        return $this->estimates->recordVendorSelections($companyIds);
    }

    /**
     * 設計部選定の確定。選択された見積先（EstimateUnitCompany）を設計部選定済みとして確定する。
     *
     * 項目ごとに設計部選定は1社のため、選択を含むユニット内の他業者は選定解除される。
     *
     * @param  list<int>  $companyIds 選定された見積先（EstimateUnitCompany）の ID 配列
     * @return int 実際に選定を確定した見積先の件数
     */
    public function confirmDesignSelections(array $companyIds): int
    {
        return $this->estimates->recordDesignSelections($companyIds);
    }

    /**
     * 部長承認。選定された見積先を承認済みにする。
     *
     * @param  list<int>  $companyIds
     * @return int 更新件数
     */
    public function confirmManagerApprovals(array $companyIds): int
    {
        return $this->estimates->recordManagerApprovals($companyIds);
    }

    /**
     * 部長取消申請。対象の見積先を取消申請中にする。
     *
     * @param  list<int>  $companyIds
     * @return int 更新件数
     */
    public function confirmCancelRequests(array $companyIds): int
    {
        return $this->estimates->recordCancelRequests($companyIds);
    }

    /**
     * 部長取消承認。対象の見積先を取消承認済みにする。
     *
     * @param  list<int>  $companyIds
     * @return int 更新件数
     */
    public function confirmCancelApprovals(array $companyIds): int
    {
        return $this->estimates->recordCancelApprovals($companyIds);
    }
}
