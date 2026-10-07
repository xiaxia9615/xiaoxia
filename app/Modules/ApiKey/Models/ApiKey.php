<?php

declare(strict_types=1);

namespace App\Modules\ApiKey\Models;

use App\Models\User;
use Database\Factories\ApiKeyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ApiKey extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'name', 'key_hash', 'key_prefix',
        'status', 'last_used_at', 'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => 'integer',
            'last_used_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function generate(int $userId, string $name = 'default'): array
    {
        $plain = 'sk_' . Str::random(32);
        $model = self::create([
            'user_id'    => $userId,
            'name'       => $name,
            'key_hash'   => hash('sha256', $plain),
            'key_prefix' => substr($plain, 0, 11),
            'status'     => 1,
        ]);
        return ['id' => $model->id, 'key' => $plain, 'name' => $name];
    }

    public static function findByPlain(string $plain): ?self
    {
        return self::where('key_hash', hash('sha256', $plain))
            ->where('status', 1)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->first();
    }

    protected static function newFactory(): ApiKeyFactory
    {
        return ApiKeyFactory::new();
    }
}
