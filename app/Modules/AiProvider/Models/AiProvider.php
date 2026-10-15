<?php

declare(strict_types=1);

namespace App\Modules\AiProvider\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;

class AiProvider extends Model
{
    protected $table = 'ai_providers';

    protected $fillable = [
        'name', 'display_name', 'base_url', 'api_key', 'enabled',
    ];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
        ];
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

    public function models(): HasMany
    {
        return $this->hasMany(AiModel::class, 'provider_id');
    }
}
