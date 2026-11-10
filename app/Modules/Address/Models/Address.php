<?php

declare(strict_types=1);

namespace App\Modules\Address\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property string $phone
 * @property string $province
 * @property string $city
 * @property string $district
 * @property string $detail
 * @property bool $is_default
 */
class Address extends Model
{
    protected $fillable = [
        'user_id', 'name', 'phone', 'province', 'city', 'district', 'detail', 'is_default',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
