<?php

declare(strict_types=1);

namespace App\Modules\Distributor\Models;

use App\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DistributorRelation extends Model
{
    protected $fillable = [
        'user_id',
        'distributor_id',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'distributor_id' => 'integer',
        ];
    }

    /**
     * 下级用户
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * 上级达人
     */
    public function distributor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'distributor_id');
    }
}
