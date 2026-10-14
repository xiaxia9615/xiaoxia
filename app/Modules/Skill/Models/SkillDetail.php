<?php

declare(strict_types=1);

namespace App\Modules\Skill\Models;

use App\Models\Product;
use Database\Factories\SkillDetailFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class SkillDetail extends Model
{
    use HasFactory;

    protected $table = 'skill_details';

    protected $fillable = [
        'product_id', 'endpoint', 'method', 'api_key', 'request_template',
        'pricing_type', 'provider_share', 'platform_share', 'influencer_share',
        'donation_share', 'free_quota', 'config',
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
            'request_template' => 'array',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function getApiKeyAttribute(?string $value): ?string
    {
        if ($value === null) return null;
        return Crypt::decryptString($value);
    }

    public function setApiKeyAttribute(?string $value): void
    {
        if ($value === null) {
            $this->attributes['api_key'] = null;
            return;
        }
        $this->attributes['api_key'] = Crypt::encryptString($value);
    }

    protected static function newFactory(): SkillDetailFactory
    {
        return SkillDetailFactory::new();
    }
}
