<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * コメント（ポリモーフィック）。任意のモデルに対するコメント1件。
 *
 * テーブル定義（マイグレーション）は felix_total 側に存在し実行済みのため、
 * fix は default 接続（＝felix_total と共有の MySQL: DB_DATABASE=fix）を通じて参照する。
 * ここでは接続を明示しない（他の T* モデルと同じく default 接続を使う）。
 *
 * commentable_type = 対象モデルの FQCN（例: App\Models\TBuildingCostItem）
 * commentable_id   = 対象レコードの id
 * user_id          = 投稿者（admin_users.id）
 *
 * @property int $id
 * @property string $commentable_type
 * @property int $commentable_id
 * @property int $user_id
 * @property string $body
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @mixin \Eloquent
 */
class TComment extends Model
{
    protected $table = 't_comments';

    protected $fillable = [
        'commentable_type',
        'commentable_id',
        'user_id',
        'body',
    ];

    /** コメント対象（ポリモーフィック）。 */
    public function commentable(): MorphTo
    {
        return $this->morphTo();
    }

    /** 投稿者（admin_users）。 */
    public function user(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'user_id');
    }

    /** 添付ファイル（t_attachments のポリモーフィック）。 */
    public function attachments(): MorphMany
    {
        return $this->morphMany(TAttachment::class, 'attachable');
    }
}
