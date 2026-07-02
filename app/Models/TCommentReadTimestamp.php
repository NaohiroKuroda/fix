<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * コメント既読（ポリモーフィック）。コメント対象モデルに対し、ユーザーごとの最終閲覧日時を持つ。
 *
 * コメント×ユーザー毎に既読フラグを持つとレコードが膨れるため、対象（readable）単位で
 * ユーザーの last_read_at を1行だけ保持する。
 * 未読は t_comment_read_timestamps.last_read_at < t_comments.created_at で判定する。
 *
 * テーブル定義（マイグレーション）は felix_total 側に存在し実行済みのため、
 * fix は default 接続（＝felix_total と共有の MySQL）を通じて参照する。
 *
 * @property int $id
 * @property int $user_id
 * @property string $readable_type
 * @property int $readable_id
 * @property Carbon|null $last_read_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @mixin \Eloquent
 */
class TCommentReadTimestamp extends Model
{
    protected $table = 't_comment_read_timestamps';

    protected $fillable = [
        'user_id',
        'readable_type',
        'readable_id',
        'last_read_at',
    ];

    protected $casts = [
        'last_read_at' => 'datetime',
    ];

    /** 既読対象（ポリモーフィック）。 */
    public function readable(): MorphTo
    {
        return $this->morphTo();
    }

    /** ユーザー（admin_users）。 */
    public function user(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'user_id');
    }
}
