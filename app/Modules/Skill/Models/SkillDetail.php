<?php

declare(strict_types=1);

namespace App\Modules\Skill\Models;

use App\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SkillDetail extends Model
{
    protected $table = 'skill_details';

    protected $fillable = [
        'product_id',
        'endpoint',
        'method',
        'pricing_type',
        'provider_share',
        'platform_share',
        'influencer_share',
        'donation_share',
        'free_quota',
        'config',
    ];

    protected function casts(): array
    {
        return [
            'product_id'       => 'integer',
            'pricing_type'     => 'integer',
            'provider_share'   => 'integer',
            'platform_share'   => 'integer',
            'influencer_share' => 'integer',
            'donation_share'   => 'integer',
            'free_quota'       => 'integer',
            'config'           => 'array',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
