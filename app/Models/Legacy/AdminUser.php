<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * @property int $id
 * @property string $username
 * @property string $password
 * @property string $name
 * @property string|null $avatar
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property int|null $backlog_id
 * @property int|null $level
 * @property int $department_id
 * @property int|null $team_id
 * @property int|null $sort
 *
 * @mixin \Eloquent
 */
class AdminUser extends Authenticatable
{
    use Notifiable;

    protected $table = 'admin_users';

    protected $fillable = [
        'username', // 💡 ログインに使うのは email ではなく username
        'name',
        'password',
        'avatar',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * 付与されているロール（admin_role_users 経由の多対多）。
     * ピボットにタイムスタンプは無い想定のため withTimestamps は付けない。
     *
     * @return BelongsToMany<AdminRole, $this>
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(AdminRole::class, 'admin_role_users', 'user_id', 'role_id');
    }

    /**
     * 付与されているロールの slug 一覧。
     *
     * @return list<string>
     */
    public function roleSlugs(): array
    {
        return $this->roles()->pluck('slug')->all();
    }

    /**
     * 建設部部長か（部長承認・差し戻し等の権限判定）。
     * 判定基準の slug は config/felix.php（manager_role_slugs）を唯一の正とする。
     */
    public function isEstimateManager(): bool
    {
        return (bool) array_intersect($this->roleSlugs(), config('felix.manager_role_slugs', []));
    }

    /**
     * 社長か（入金仮締めの権限判定）。
     * 判定基準の slug は config/felix.php（president_role_slugs）を唯一の正とする。
     */
    public function isPresident(): bool
    {
        return (bool) array_intersect($this->roleSlugs(), config('felix.president_role_slugs', []));
    }

    /**
     * サイドメニューの表示可否（メニューキー => 表示するか）。
     *
     * 新テーブルのメニュー定義を唯一の正とする：
     *   m_users（source_id = admin_users.id）→ p_user_roles → p_role_permissions
     *     ┗ p_user_permissions（ユーザー個別の権限）
     *   → p_permission_menu_items → m_menu_items.uri
     * メニューキーは `uri` の末尾セグメント（`/quotation-management/quote-request` → `quote-request`）。
     * 画面が増えたときは m_menu_items に1行足して権限へ紐づければ表示される（コード変更は不要）。
     *
     * @return array<string, bool>
     */
    public function menuPermissions(): array
    {
        $userId = DB::table('m_users')
            ->where('source_table', 'admin_users')
            ->where('source_id', $this->id)
            ->value('id');
        if ($userId === null) {
            return [];
        }

        $viaRoles = DB::table('p_role_permissions as rp')
            ->join('p_user_roles as ur', 'ur.role_id', '=', 'rp.role_id')
            ->where('ur.user_id', $userId)
            ->pluck('rp.permission_id');
        $direct = DB::table('p_user_permissions')->where('user_id', $userId)->pluck('permission_id');

        $uris = DB::table('m_menu_items')
            ->whereNotNull('uri')
            ->whereIn('id', DB::table('p_permission_menu_items')
                ->whereIn('permission_id', $viaRoles->merge($direct)->unique())
                ->select('menu_item_id'))
            ->pluck('uri');

        $permissions = [];
        foreach ($uris as $uri) {
            $permissions[basename((string) $uri)] = true;
        }

        return $permissions;
    }
}
