<?php

declare(strict_types=1);

namespace App\Modules\User\Models;

use App\Modules\User\Enums\DeveloperStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    protected $guarded = []; // 解除所有赋值限制

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
