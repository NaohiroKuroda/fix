<?php

namespace App\Http\Resources;

use App\Models\EstimateUnitCompany;
use App\Utils\Format;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

/**
 * 発注先（業者）1件分の表示用データ。
 *
 * 旧 EstimateUnitCompany の各アクセサ（last_price / *_company_select_status /
 * *_complete_status / history_count / count_estimate など）が生成していた
 * HTML+ロジックを、純粋なデータ（状態+日付+ラベル）に再構成したもの。
 *
 * @mixin EstimateUnitCompany
 */
class EstimateUnitCompanyResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $completeStatus = (int) ($this->unit->complete_status ?? 0);

        return [
            // estimate_unit_companies.id（felix_total の /admin 編集 iframe を開くのに使う）
            'id' => (int) $this->id,

            // 発注先
            'name' => $this->mainCompanyName(),
            'payName' => $this->payCompanyName(),
            'adopted' => (int) $this->adoption_flg === 1,
            'changed' => (int) $this->changed_flg === 1,
            'emphasized' => (bool) ($this->invoice_flg || $this->order_flg),

            // 相見積(税抜)
            'lastPrice' => Format::yen($this->latestEstimateFile()?->price),

            // 選定（建設部が選定済みか）
            'selected' => (int) $this->tmp_status === 1,

            // 見積UP（見積書ファイル）
            'estimateUp' => $this->estimateUpState(),

            // 依頼（相見積送信履歴）
            'request' => $this->requestState(),

            // 建設部選定 / 設計部選定 / 常務承認
            'buildSelect' => $this->selectionState((int) $this->tmp_status, 'tmp_company'),
            'designSelect' => $this->selectionState((int) $this->design_status, 'design_company', (string) ($this->denial_comment ?? '')),
            'execApproval' => $this->selectionState((int) $this->fix_status, 'fix_company'),

            // 完了報告（第一/第二/第三承認）
            'approval1' => $this->approvalState($completeStatus, 1),
            'approval2' => $this->approvalState($completeStatus, 2),
            'approval3' => $this->approvalState($completeStatus, 3),
        ];
    }

    private function mainCompanyName(): string
    {
        $name = $this->company?->company_name;
        if ($name === null) {
            return '（業者未設定）';
        }

        $branch = $this->branch;
        if ($branch !== null && (int) $this->branch_id !== (int) $this->company_id && $branch->company_name) {
            return $name.' '.$branch->company_name;
        }

        return $name;
    }

    private function payCompanyName(): ?string
    {
        if (! $this->pay_company_id) {
            return null;
        }

        return $this->payCompany?->company_name;
    }

    /** type=1（見積書）の最新ファイル。 */
    private function latestEstimateFile(): ?object
    {
        return $this->loadedFiles()
            ->where('type', 1)
            ->sortByDesc('date')
            ->sortByDesc('id')
            ->first();
    }

    /** 見積UP: 最新見積ファイルの日付 + 承認状態。 */
    private function estimateUpState(): array
    {
        $file = $this->latestEstimateFile();
        $count = $this->loadedFiles()->where('type', 1)->count();

        if ($file?->date) {
            return [
                'state' => $this->adoption_flg ? 'approved' : 'pending',
                'label' => '承認済',
                'date' => Format::date($file->date),
                'count' => $count,
            ];
        }

        return ['state' => 'none', 'label' => '未承認', 'date' => '', 'count' => $count];
    }

    /** 依頼: 相見積送信履歴の件数 + 最新送信日。 */
    private function requestState(): array
    {
        $histories = $this->relationLoaded('orderHistories') ? $this->orderHistories : collect();
        $count = $histories->count();

        if ($count > 0) {
            $latest = $histories->sortByDesc('created_at')->first();

            return [
                'state' => $this->cancel_flg ? 'notified' : 'sent',
                'label' => $this->cancel_flg ? '通知済' : '送信済',
                'date' => Format::date($latest?->created_at),
                'count' => $count,
            ];
        }

        return ['state' => 'none', 'label' => '未送信', 'date' => '', 'count' => 0];
    }

    /**
     * 建設部/設計部/常務の選定状態。
     * status: 0=未承認, 1=承認済, 2=否認済。日付は変更履歴の最新 created_at。
     */
    private function selectionState(int $status, string $historyType, string $denial = ''): array
    {
        $labels = config('status_management.select_state');
        $state = match ($status) {
            1 => 'approved',
            2 => 'denied',
            default => 'pending',
        };

        $history = ($this->relationLoaded('changeHistories') ? $this->changeHistories : collect())
            ->where('type', $historyType)
            ->sortByDesc('created_at')
            ->first();

        return [
            'state' => $state,
            'label' => $labels[$status] ?? '',
            'date' => Format::date($history?->created_at),
            'denial' => $status === 2 ? $denial : '',
        ];
    }

    /**
     * 完了報告の承認（step=1/2/3）。
     * 採用業者のみ対象。unit.complete_status と end_reports/complete_approval_histories から判定。
     */
    private function approvalState(int $completeStatus, int $step): array
    {
        $none = ['state' => 'none', 'label' => '', 'date' => ''];

        if ((int) $this->adoption_flg !== 1) {
            return $none;
        }

        $endReports = $this->relationLoaded('endReports') ? $this->endReports : collect();
        $completeHistories = $this->relationLoaded('completeHistories') ? $this->completeHistories : collect();

        // 否認チェック（差し戻し）
        $deniedAt = function (int $status, int $denialFlg) use ($endReports): ?string {
            $er = $endReports->first(fn ($r) => (int) $r->status === $status && (int) $r->denial_flg === $denialFlg);

            return $er?->updated_at;
        };
        // 承認済チェック
        $approvedAt = function (int $status) use ($completeHistories): ?string {
            $h = $completeHistories->where('status', $status)->sortBy('id')->first();

            return $h?->created_at;
        };

        // 承認済かどうかは complete_status の到達段階で判定し、日付は履歴があれば補完する。
        return match ($step) {
            1 => $this->resolveApproval(
                approved: $completeStatus > 1,
                approvedDate: $approvedAt(2),
                deniedDate: $completeStatus === 1 ? $deniedAt(1, 2) : null,
                pending: $completeStatus === 1,
            ),
            2 => $this->resolveApproval(
                approved: $completeStatus > 2,
                approvedDate: $approvedAt(3),
                deniedDate: $completeStatus === 1 ? $deniedAt(1, 2) : ($completeStatus === 2 ? $deniedAt(2, 3) : null),
                pending: $completeStatus === 2,
            ),
            3 => $this->resolveApproval(
                approved: $completeStatus > 3,
                approvedDate: $approvedAt(4),
                deniedDate: $completeStatus === 2 ? $deniedAt(2, 3) : null,
                pending: $completeStatus === 3,
            ),
            default => $none,
        };
    }

    /** 承認状態の優先度判定（承認済 > 否認済 > 未承認 > なし）。 */
    private function resolveApproval(bool $approved, ?string $approvedDate, ?string $deniedDate, bool $pending): array
    {
        if ($approved) {
            return ['state' => 'approved', 'label' => '承認済', 'date' => Format::date($approvedDate)];
        }
        if ($deniedDate !== null && $deniedDate !== '') {
            return ['state' => 'denied', 'label' => '否認済', 'date' => Format::date($deniedDate)];
        }
        if ($pending) {
            return ['state' => 'pending', 'label' => '未承認', 'date' => ''];
        }

        return ['state' => 'none', 'label' => '', 'date' => ''];
    }

    /** @return Collection<int, object> */
    private function loadedFiles(): Collection
    {
        return $this->relationLoaded('files') ? $this->files : collect();
    }
}
