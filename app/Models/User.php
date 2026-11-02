<?php

declare(strict_types=1);

namespace App\Models;

use App\Modules\User\Models\User as ModuleUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class User extends ModuleUser
{
    use HasFactory;

    // 明确指定工厂，防止找不到 Database\Factories\UserFactory
    protected static function newFactory()
    {
        return \Database\Factories\UserFactory::new();
    }
}
