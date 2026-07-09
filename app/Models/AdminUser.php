<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;

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
     * 全メニューを表示する管理者ロールか。
     * 判定基準の slug は config/felix.php（admin_role_slugs）を唯一の正とする。
     */
    public function isAdministrator(): bool
    {
        return (bool) array_intersect($this->roleSlugs(), config('felix.admin_role_slugs', []));
    }

    /**
     * サイドメニューの表示可否（メニューキー => 表示するか）。
     * config/felix.php（menu_roles）を唯一の正とし、administrator は全メニューを表示する。
     * 発注管理などメニューが増えても menu_roles に追記すれば自動的に反映される。
     *
     * @return array<string, bool>
     */
    public function menuPermissions(): array
    {
        $slugs = $this->roleSlugs();
        $isAdmin = (bool) array_intersect($slugs, config('felix.admin_role_slugs', []));

        $permissions = [];
        foreach ((array) config('felix.menu_roles', []) as $menuKey => $allowedSlugs) {
            $permissions[$menuKey] = $isAdmin || (bool) array_intersect($slugs, (array) $allowedSlugs);
        }

        return $permissions;
    }
}
