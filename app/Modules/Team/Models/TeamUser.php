<?php

declare(strict_types=1);

namespace App\Modules\Team\Models;

use App\Models\User;
use Database\Factories\TeamUserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeamUser extends Model
{
    use HasFactory;

    protected $table = 'team_user';

    protected $fillable = [
        'team_id',
        'user_id',
        'role',
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected static function newFactory(): TeamUserFactory
    {
        return TeamUserFactory::new();
    }
}
