<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\Concerns\HasBlameColumns;
use App\Models\TComment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * 作成者 / 更新者（created_by / updated_by）の自動記録
 * （{@see HasBlameColumns}）の検証。
 *
 * 新見積管理系テーブルのマイグレーションは felix_total 側にあり fix には無いため、
 * ここでは代表として t_comments 相当のテーブルをテスト用に組み立てて検証する。
 */
class HasBlameColumnsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('t_comments', function ($table): void {
            $table->id();
            $table->string('commentable_type');
            $table->unsignedBigInteger('commentable_id');
            $table->unsignedBigInteger('user_id');
            $table->text('body');
            $table->dateTime('created_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
        });
    }

    /** ログイン中は、作成時に created_by / updated_by が操作者で埋まる。 */
    public function test_it_stamps_created_by_and_updated_by_on_create(): void
    {
        $this->actingAsAdmin(42);

        $comment = TComment::create($this->attributes());

        $this->assertSame(42, (int) $comment->fresh()->created_by);
        $this->assertSame(42, (int) $comment->fresh()->updated_by);
    }

    /** 更新時は updated_by だけが操作者で上書きされ、created_by は作成者のまま。 */
    public function test_it_stamps_only_updated_by_on_update(): void
    {
        $this->actingAsAdmin(42);
        $comment = TComment::create($this->attributes());

        $this->actingAsAdmin(99);
        $comment->body = '編集後';
        $comment->save();

        $comment = $comment->fresh();
        $this->assertSame(42, (int) $comment->created_by);
        $this->assertSame(99, (int) $comment->updated_by);
    }

    /** 未ログイン（バッチ・コンソール実行）では押印せず null のまま。 */
    public function test_it_leaves_columns_null_when_not_authenticated(): void
    {
        $comment = TComment::create($this->attributes());

        $this->assertNull($comment->fresh()->created_by);
        $this->assertNull($comment->fresh()->updated_by);
    }

    /** 呼び出し側が明示的に指定した created_by は上書きしない（移行・同期での引き継ぎ用）。 */
    public function test_it_does_not_overwrite_explicit_created_by(): void
    {
        $this->actingAsAdmin(42);

        $comment = new TComment($this->attributes());
        $comment->created_by = 7;
        $comment->save();

        $this->assertSame(7, (int) $comment->fresh()->created_by);
        $this->assertSame(42, (int) $comment->fresh()->updated_by);
    }

    private function actingAsAdmin(int $id): void
    {
        $admin = new AdminUser;
        $admin->id = $id;

        Auth::guard('admin')->setUser($admin);
    }

    /** @return array<string, mixed> */
    private function attributes(): array
    {
        return [
            'commentable_type' => 'App\Models\TBuildingCostItem',
            'commentable_id' => 1,
            'user_id' => 42,
            'body' => 'テスト',
        ];
    }
}
