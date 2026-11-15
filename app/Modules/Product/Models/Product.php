<?php

declare(strict_types=1);

namespace App\Modules\Product\Models;

use App\Models\User;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $type
 * @property int $price
 * @property int $status
 * @property int $sales_count
 * @property int $view_count
 * @property string $name
 * @property int $user_id
 * @property string|null $description
 * @property string $nature
 * @property int $quota
 * @property int|null $skill_id
 * @property string|null $cover
 * @property \Illuminate\Support\Carbon|null $created_at
 */
class Product extends Model
{
    use HasFactory;
    use SoftDeletes;

    /** 商品性质：实物 */
    public const NATURE_PHYSICAL = 'physical';

    /** 商品性质：虚拟 */
    public const NATURE_VIRTUAL = 'virtual';

    protected $fillable = [
        'user_id', 'type', 'name', 'slug', 'description',
        'cover', 'price', 'status', 'audit_note',
        'nature', 'quota', 'skill_id',
    ];

    protected function casts(): array
    {
        return [
            'type' => 'integer',
            'price' => 'integer',
            'status' => 'integer',
            'sales_count' => 'integer',
            'view_count' => 'integer',
            'quota' => 'integer',
            'skill_id' => 'integer',
        ];
    }

    /** 是否虚拟商品（虚拟服务：自动发货） */
    public function isVirtual(): bool
    {
        return $this->nature === self::NATURE_VIRTUAL;
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function scopeOnSale($query)
    {
        return $query->where('status', 1);
    }

    public function scopeSkills($query)
    {
        return $query->where('type', 1);
    }

    public function scopeGoods($query)
    {
        return $query->where('type', 2);
    }

    protected static function newFactory(): ProductFactory
    {
        return ProductFactory::new();
    }
}
