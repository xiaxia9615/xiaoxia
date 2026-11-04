<?php

declare(strict_types=1);

namespace App\Modules\Wallet\Models;

use Illuminate\Database\Eloquent\Model;

class Wallet extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'balance' => 'integer',
            'frozen_balance' => 'integer',
        ];
    }
}
