<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * ポリモーフィック型に残っている旧クラス名を新クラス名へ書き換える。
 *
 * 2026-08 のスキーマ改訂で費用項目のモデルを TBuildingCostItem → TBuildingBudgetItem へ改称したが、
 * 改称前に保存された行は旧 FQCN のままだった。これまでは morphMap で旧 FQCN を別名として登録して
 * 読み書きしていたため、**新しく登録されるコメントにも旧クラス名が入り続けていた**。
 *
 * 本マイグレーションで既存行を新 FQCN へ寄せ、morphMap の別名登録は廃止する
 * （AppServiceProvider::boot()）。以後は getMorphClass() が実クラス名を返す。
 */
return new class extends Migration
{
    private const OLD = 'App\Models\TBuildingCostItem';

    private const NEW = 'App\Models\TBuildingBudgetItem';

    public function up(): void
    {
        $this->rename(self::OLD, self::NEW);
    }

    public function down(): void
    {
        $this->rename(self::NEW, self::OLD);
    }

    /**
     * 対象テーブルのモーフ型を書き換える。
     *
     * 見積管理系のテーブルはマイグレーション管理外（felix_total と共有の既存 DB にある）ため、
     * テスト用の sqlite など**テーブルが存在しない環境では何もしない**。
     */
    private function rename(string $from, string $to): void
    {
        if (Schema::hasTable('t_comments')) {
            DB::table('t_comments')
                ->where('commentable_type', $from)
                ->update(['commentable_type' => $to]);
        }

        if (Schema::hasTable('t_comment_read_timestamps')) {
            DB::table('t_comment_read_timestamps')
                ->where('readable_type', $from)
                ->update(['readable_type' => $to]);
        }
    }
};
