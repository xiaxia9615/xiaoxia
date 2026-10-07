<?php

declare(strict_types=1);

namespace App\Modules\ApiKey\Models;

use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApiCall extends Model
{
    protected $table = 'api_calls';

    protected $fillable = [
        'request_id',
        'user_id',
        'api_key_id',
        'product_id',
        'provider_id',
        'input',
        'output',
        'status_code',
        'duration_ms',
        'cost',
        'provider_income',
        'platform_income',
        'influencer_income',
        'donation_amount',
    ];

    protected function casts(): array
    {
        return [
            'user_id'           => 'integer',
            'api_key_id'        => 'integer',
            'product_id'        => 'integer',
            'provider_id'       => 'integer',
            'input'             => 'array',
            'output'            => 'array',
            'status_code'       => 'integer',
            'duration_ms'       => 'integer',
            'cost'              => 'integer',
            'provider_income'   => 'integer',
            'platform_income'   => 'integer',
            'influencer_income' => 'integer',
            'donation_amount'   => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function apiKey(): BelongsTo
    {
        return $this->belongsTo(ApiKey::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
