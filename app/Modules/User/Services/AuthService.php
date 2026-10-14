<?php

declare(strict_types=1);

namespace App\Modules\User\Services;

use App\Models\User;
use App\Modules\Ledger\Services\LedgerService;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

final class AuthService
{
    public function __construct(private readonly LedgerService $ledger) {}

    public function register(string $name, string $email, string $password): array
    {
        $user = User::create(['name'=>$name,'email'=>$email,'password'=>Hash::make($password),'status'=>1]);

        // 注册送 100 元体验金（10000 分）
        $this->ledger->record('signup_bonus', $user->id, [
            ['owner_type'=>'platform','owner_id'=>0,'account_type'=>'platform','direction'=>'debit','amount'=>100000],
            ['owner_type'=>'user','owner_id'=>$user->id,'account_type'=>'balance','direction'=>'credit','amount'=>100000],
        ]);

        return ['user'=>['id'=>$user->id,'name'=>$user->name,'email'=>$user->email],'token'=>$user->createToken('api')->plainTextToken];
    }

    public function login(string $email, string $password): array
    {
        $user = User::where('email', $email)->first();
        if (! $user || ! Hash::check($password, $user->password)) throw new RuntimeException('invalid credentials');
        return ['user'=>['id'=>$user->id,'name'=>$user->name,'email'=>$user->email],'token'=>$user->createToken('api')->plainTextToken];
    }
}
