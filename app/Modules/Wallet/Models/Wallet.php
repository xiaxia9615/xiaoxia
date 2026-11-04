<?php

declare(strict_types=1);

namespace App\Modules\Wallet\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
