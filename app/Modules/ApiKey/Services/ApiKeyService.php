<?php

declare(strict_types=1);

namespace App\Modules\ApiKey\Services;

use App\Models\User;
use App\Modules\ApiKey\Models\ApiKey;
use Illuminate\Support\Str;

final class ApiKeyService
{
    public function generate(User $user, string $name = 'default'): array
    {
        $plain = 'sk_' . Str::random(32);
        $key = ApiKey::create(['user_id'=>$user->id,'name'=>$name,'key_hash'=>hash('sha256',$plain),'key_prefix'=>substr($plain,0,11),'status'=>1]);
        return ['id'=>$key->id,'name'=>$name,'key'=>$plain];
    }

    public function listByUser(User $user): array
    {
        return ApiKey::where('user_id', $user->id)->orderByDesc('id')->get(['id','name','key_prefix','status','last_used_at','created_at'])->toArray();
    }

    public function revoke(User $user, int $id): bool
    {
        return (bool) ApiKey::where('user_id', $user->id)->where('id', $id)->update(['status'=>0]);
    }
}
