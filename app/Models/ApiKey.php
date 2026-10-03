<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ApiKey extends Model
{
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

    /**
     * 生成新 key，返回明文（只返回一次）
     * 格式：sk_ + 32位随机字符
     */
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

        return [
            'id'    => $model->id,
            'key'   => $plain,
            'name'  => $name,
        ];
    }

    /**
     * 根据明文 key 查找有效记录
     */
    public static function findByPlain(string $plain): ?self
    {
        return self::where('key_hash', hash('sha256', $plain))
            ->where('status', 1)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            })
            ->first();
    }
}
