<?php

declare(strict_types=1);

namespace App\Modules\Message\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property string $title
 * @property string $content
 * @property bool $is_read
 */
class Message extends Model
{
    protected $fillable = [
        'user_id', 'title', 'content', 'is_read',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'is_read' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
