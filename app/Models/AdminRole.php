<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * felix_total（laravel-admin）のロール。admin_role_users ピボットで AdminUser と多対多。
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @mixin \Eloquent
 */
class AdminRole extends Model
{
    protected $table = 'admin_roles';

    protected $fillable = [
        'name',
        'slug',
    ];
}
