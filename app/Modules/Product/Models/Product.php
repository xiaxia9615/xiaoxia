<?php

declare(strict_types=1);

namespace App\Modules\Product\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id', 'type', 'name', 'slug', 'description',
        'cover', 'price', 'status', 'audit_note',
    ];

    protected function casts(): array
    {
        return [
            'type' => 'integer',
            'price' => 'integer',
            'status' => 'integer',
            'sales_count' => 'integer',
            'view_count' => 'integer',
        ];
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
}
