<?php

declare(strict_types=1);

namespace App\Modules\User\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Modules\User\Enums\DeveloperStatus;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $guarded = []; // 暴力解除所有限制

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'is_active' => 'boolean',
            'is_developer' => 'boolean',
            'developer_status' => DeveloperStatus::class,
        ];
    }
}
