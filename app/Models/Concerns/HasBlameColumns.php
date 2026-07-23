<?php

namespace App\Models\Concerns;

use App\Utils\Blame;
use Illuminate\Database\Eloquent\Model;

/**
 * 作成者 / 更新者（created_by / updated_by）の自動記録。
 *
 * created_at / updated_at をフレームワークが自動で入れるのと同じ要領で、
 * 操作したログイン管理ユーザー（admin_users.id）を保存時に押印する。
 * 新見積管理系のテーブルは全て両列を持つため、対応するモデルに本トレイトを use する。
 *
 * - 作成時（creating）: created_by / updated_by を操作者で埋める。
 * - 更新時（updating）: updated_by を操作者で上書きする。
 * - 呼び出し側が明示的に値を入れている場合は尊重し、上書きしない
 *   （現行 felix_total からの移行・同期で作成者を引き継ぐケースがあるため）。
 * - 未ログイン（バッチ・コンソール実行）では何もしない。両列は nullable。
 *
 * 一括更新（`Model::query()->update([...])`）はモデルイベントが発火しないため本トレイトの対象外。
 * その場合は {@see Blame::stampUpdate()} で呼び出し側が明示的に押印する。
 */
trait HasBlameColumns
{
    public static function bootHasBlameColumns(): void
    {
        static::creating(function (Model $model): void {
            $userId = Blame::userId();

            if ($userId === null) {
                return;
            }

            if ($model->getAttribute('created_by') === null) {
                $model->setAttribute('created_by', $userId);
            }

            if ($model->getAttribute('updated_by') === null) {
                $model->setAttribute('updated_by', $userId);
            }
        });

        static::updating(function (Model $model): void {
            $userId = Blame::userId();

            // 呼び出し側が updated_by を明示していれば、その値を優先する。
            if ($userId === null || $model->isDirty('updated_by')) {
                return;
            }

            $model->setAttribute('updated_by', $userId);
        });
    }
}
