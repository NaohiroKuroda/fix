<?php

namespace App\Repositories\Contracts\Mail;

use App\Models\Company;
use App\Models\EstimateUnitCompany;
use App\Models\TBillingPartner;
use App\Models\TPayablePartner;
use Illuminate\Database\Eloquent\Collection;

/**
 * 業者への通知メールを組み立てるためのデータ取得窓口（支払・請求で共用）。
 *
 * 新スキーマ（`t_billing_partners` / `t_payable_partners`）と現行スキーマ
 * （`estimate_unit_companies` / `company_staff` / `company_tokens`）の両方を読むが、
 * 業務判断（会社単位のまとめ方・宛先の優先順位）は Service 側に置く。
 */
interface VendorMailRepositoryInterface
{
    /**
     * 通知対象の請求取引先を、物件名・項目名つきで取得する。
     * 移行元（`source_id`）の無い取引先は業者マイページの URL を組めないため除外する。
     *
     * @param  list<int>  $partnerIds  `t_billing_partners.id`
     * @return Collection<int, TBillingPartner>
     */
    public function findPartnersForMail(array $partnerIds): Collection;

    /**
     * 通知対象の支払取引先を、物件名・項目名つきで取得する（{@see findPartnersForMail} の支払版）。
     *
     * @param  list<int>  $partnerIds  `t_payable_partners.id`
     * @return Collection<int, TPayablePartner>
     */
    public function findPayablePartnersForMail(array $partnerIds): Collection;

    /**
     * 現行の見積業者を ID で引く（`t_billing_partners.source_id` に対応）。
     *
     * @param  list<int>  $ids  `estimate_unit_companies.id`
     * @return Collection<int, EstimateUnitCompany> キーは `id`
     */
    public function findLegacyCompaniesByIds(array $ids): Collection;

    /**
     * 業者（`companies`）を ID で引く。
     *
     * @param  list<int>  $ids
     * @return Collection<int, Company> キーは `id`
     */
    public function findCompaniesByIds(array $ids): Collection;

    /**
     * 有効期間内の担当者メールアドレスを取得する。
     *
     * @param  list<int|string>  $staffIds  `company_staff.id`
     * @return list<string>
     */
    public function findActiveStaffEmails(array $staffIds): array;

    /**
     * 業者マイページのアクセストークンを取得する（会社単位。無ければ発行して使い回す）。
     */
    public function firstOrCreateAccessToken(int $companyId): string;
}
