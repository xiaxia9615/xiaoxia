<?php

declare(strict_types=1);

namespace App\Modules\User\Models;

use App\Modules\User\Enums\DeveloperStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property Carbon|null $email_verified_at
 * @property bool $is_admin
 * @property bool $is_finance
 * @property bool $is_super_admin
 * @property bool $is_active
 * @property bool $is_developer
 * @property string $name
 * @property string $email
 * @property int $status
 * @property string $developer_status
 */
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    // 敏感字段禁止批量赋值，需直接赋值或 forceFill
    protected $guarded = [
        'is_admin',
        'is_finance',
        'is_super_admin',
        'is_developer',
        'is_active',
        'status',
        'balance',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'is_finance' => 'boolean',
            'is_super_admin' => 'boolean',
            'is_active' => 'boolean',
            'is_developer' => 'boolean',
            'developer_status' => DeveloperStatus::class,
        ];
    }
}
