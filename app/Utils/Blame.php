<?php

namespace App\Utils;

use App\Models\Concerns\HasBlameColumns;
use Illuminate\Support\Facades\Auth;

/**
 * 作成者 / 更新者（created_by / updated_by）の解決ユーティリティ。
 *
 * 新見積管理系のテーブル（t_buildings / t_building_cost_items / t_cost_quotations /
 * t_cost_quotation_histories / t_comments 等）は作成者 ID・更新者 ID を保持する。
 * その値は「操作中のログイン管理ユーザー（admin_users.id）」であり、その解決を本クラスに一本化する。
 *
 * Eloquent 経由の作成 / 更新は {@see HasBlameColumns} が自動で押印するため、
 * 本クラスを直接使うのは、モデルイベントが発火しない一括更新
 * （`Model::query()->update([...])`）を行う Repository に限る。
 *
 * バッチ・コンソール実行など未ログイン時は null（列は nullable）を返す。
 */
class Blame
{
    /** 操作者（ログイン中の admin_users.id）。未ログインなら null。 */
    public static function userId(): ?int
    {
        $id = Auth::guard('admin')->id();

        return $id === null ? null : (int) $id;
    }

    /**
     * 一括更新（`Model::query()->update([...])`）用に updated_by を差し込む。
     * 一括更新はモデルイベントが発火しないため、押印は呼び出し側で明示する必要がある。
     * 呼び出し側が updated_by を指定済みの場合・未ログインの場合はそのまま返す。
     *
     * @param  array<string, mixed>  $values  更新する列と値
     * @return array<string, mixed>
     */
    public static function stampUpdate(array $values): array
    {
        $userId = self::userId();

        if ($userId === null) {
            return $values;
        }

        // `+` は既存キーを優先するため、呼び出し側の明示指定を上書きしない。
        return $values + ['updated_by' => $userId];
    }
}
