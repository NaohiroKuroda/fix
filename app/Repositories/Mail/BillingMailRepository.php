<?php

namespace App\Repositories\Mail;

use App\Models\Company;
use App\Models\CompanyStaff;
use App\Models\CompanyToken;
use App\Models\EstimateUnitCompany;
use App\Models\TBillingPartner;
use App\Repositories\Contracts\Mail\BillingMailRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * 請求（もらい）系の通知メール用データ取得。
 *
 * 現行 felix_total の `EstimateUnitCompany::$pic_mail` アクセサ・
 * `EstimateCustomDetailController::create_token()` と同じ解決規則を踏襲する。
 */
class BillingMailRepository implements BillingMailRepositoryInterface
{
    /**
     * @param  list<int>  $partnerIds
     * @return Collection<int, TBillingPartner>
     */
    public function findPartnersForMail(array $partnerIds): Collection
    {
        if ($partnerIds === []) {
            /** @var Collection<int, TBillingPartner> */
            return new Collection;
        }

        return TBillingPartner::query()
            ->whereIn('id', $partnerIds)
            // 移行元が無いと業者マイページの URL を組めないため対象外（現行の見積依頼と同じ扱い）。
            ->whereNotNull('source_id')
            ->with(['budgetItem.building'])
            ->orderBy('id')
            ->get();
    }

    /**
     * @param  list<int>  $ids
     * @return Collection<int, EstimateUnitCompany>
     */
    public function findLegacyCompaniesByIds(array $ids): Collection
    {
        if ($ids === []) {
            /** @var Collection<int, EstimateUnitCompany> */
            return new Collection;
        }

        return EstimateUnitCompany::query()
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');
    }

    /**
     * @param  list<int>  $ids
     * @return Collection<int, Company>
     */
    public function findCompaniesByIds(array $ids): Collection
    {
        if ($ids === []) {
            /** @var Collection<int, Company> */
            return new Collection;
        }

        return Company::query()
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');
    }

    /**
     * 有効期間の判定は現行の `pic_mail` と同じ（両端あり／両端なし／片側のみ の4パターン）。
     *
     * @param  list<int|string>  $staffIds
     * @return list<string>
     */
    public function findActiveStaffEmails(array $staffIds): array
    {
        if ($staffIds === []) {
            return [];
        }

        $today = Carbon::now()->format('Y-m-d');

        return CompanyStaff::query()
            ->whereIn('id', $staffIds)
            ->where(function ($query) use ($today) {
                $query
                    // 期間が両方入っている場合はその間
                    ->where(function ($q) use ($today) {
                        $q->where('date_start', '<=', $today)
                            ->where('date_end', '>=', $today);
                    })
                    // どちらも未設定
                    ->orWhere(function ($q) {
                        $q->whereNull('date_start')
                            ->whereNull('date_end');
                    })
                    // 終了日のみ未設定（開始日以降）
                    ->orWhere(function ($q) use ($today) {
                        $q->where('date_start', '<=', $today)
                            ->whereNull('date_end');
                    })
                    // 開始日のみ未設定（終了日以前）
                    ->orWhere(function ($q) use ($today) {
                        $q->whereNull('date_start')
                            ->where('date_end', '>=', $today);
                    });
            })
            ->pluck('email')
            ->all();
    }

    /**
     * @param  int  $companyId
     * @return string
     */
    public function firstOrCreateAccessToken(int $companyId): string
    {
        $token = CompanyToken::query()->where('company_id', $companyId)->first();

        if ($token !== null) {
            return (string) $token->access_token;
        }

        return (string) CompanyToken::query()->create([
            'company_id' => $companyId,
            'access_token' => Str::random(32),
        ])->access_token;
    }
}
