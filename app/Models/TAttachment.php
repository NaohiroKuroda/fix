<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * 添付ファイル（ポリモーフィック）。任意のモデルに紐づくファイル1件。
 *
 * テーブル定義（マイグレーション）は felix_total 側に存在し実行済みのため、
 * fix は default 接続（＝felix_total と共有の MySQL）を通じて参照する。
 *
 * attachable_type = 対象モデルの FQCN（例: App\Models\TComment）
 * attachable_id   = 対象レコードの id
 * user_id         = アップロード実行ユーザー（admin_users.id）
 *
 * @property int $id
 * @property string $attachable_type
 * @property int $attachable_id
 * @property string $file_path
 * @property string $original_name
 * @property string $mime_type
 * @property int $size
 * @property int $user_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @mixin \Eloquent
 */
class TAttachment extends Model
{
    protected $table = 't_attachments';

    protected $fillable = [
        'attachable_type',
        'attachable_id',
        'file_path',
        'original_name',
        'mime_type',
        'size',
        'user_id',
    ];

    protected $casts = [
        'size' => 'integer',
    ];

    /** 添付対象（ポリモーフィック）。 */
    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }

    /** アップロード実行ユーザー（admin_users）。 */
    public function user(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'user_id');
    }
}
