<?php

declare(strict_types=1);

namespace App\Modules\Comment\Models;

use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property int $product_id
 * @property string $content
 * @property User|null $user
 * @property Product|null $product
 */
class Comment extends Model
{
    protected $fillable = [
        'user_id', 'product_id', 'content',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'product_id' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
