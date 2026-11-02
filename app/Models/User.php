<?php

declare(strict_types=1);

namespace App\Models;

use App\Modules\User\Models\User as ModuleUser;

class User extends ModuleUser
{
    // 这是一个兼容壳，所有逻辑都交由 App\Modules\User\Models\User 处理
}
