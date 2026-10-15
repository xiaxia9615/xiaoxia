<?php

declare(strict_types=1);

namespace App\Modules\AiProvider\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiModel extends Model
{
    protected $table = 'ai_models';

    protected $fillable = [
        'provider_id', 'name', 'display_name',
        'input_price_per_1k', 'output_price_per_1k', 'enabled',
    ];

    protected function casts(): array
    {
        return [
            'provider_id'         => 'integer',
            'input_price_per_1k'  => 'integer',
            'output_price_per_1k' => 'integer',
            'enabled'             => 'boolean',
        ];
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(AiProvider::class, 'provider_id');
    }
}
