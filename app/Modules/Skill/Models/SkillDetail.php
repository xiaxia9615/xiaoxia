<?php

declare(strict_types=1);

namespace App\Modules\Skill\Models;

use App\Models\Product;
use App\Modules\Skill\Enums\SkillStatus;
use Database\Factories\SkillDetailFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

/**
 * @property int $product_id
 * @property int|null $provider_id
 * @property int $pricing_type
 * @property int $provider_share
 * @property int $platform_share
 * @property int $influencer_share
 * @property int $donation_share
 * @property int $free_quota
 * @property array|null $config
 * @property array|null $request_template
 * @property string|null $system_prompt
 * @property string $endpoint
 * @property string $method
 */
class SkillDetail extends Model
{
    use HasFactory;

    protected $table = 'skill_details';

    protected $fillable = [
        'product_id', 'provider_id', 'status', 'endpoint', 'method', 'api_key', 'request_template',
        'pricing_type', 'provider_share', 'platform_share', 'influencer_share',
        'donation_share', 'free_quota', 'config', 'system_prompt',
    ];

    protected function casts(): array
    {
        return [
            'product_id' => 'integer',
            'provider_id' => 'integer',
            'status' => SkillStatus::class,
            'pricing_type' => 'integer',
            'provider_share' => 'integer',
            'platform_share' => 'integer',
            'influencer_share' => 'integer',
            'donation_share' => 'integer',
            'free_quota' => 'integer',
            'config' => 'array',
            'request_template' => 'array',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function getApiKeyAttribute(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

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
