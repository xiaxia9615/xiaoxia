<?php

declare(strict_types=1);

namespace App\Modules\User\Services;

use App\Models\User;
use App\Modules\User\Dto\UserDto;
use App\Modules\User\Events\UserRegistered;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

final class AuthService
{
    public function register(string $name, string $email, string $password): array
    {
        $user = User::create([
            'name'     => $name,
            'email'    => $email,
            'password' => $password,
            'status'   => 1,
        ]);

        event(new UserRegistered(UserDto::fromModel($user)));

        return [
            'user'  => ['id' => $user->id, 'name' => $user->name, 'email' => $user->email],
            'token' => $user->createToken('api')->plainTextToken,
        ];
    }

    public function login(string $email, string $password): array
    {
        $user = User::where('email', $email)->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            throw new RuntimeException('invalid credentials');
        }

        return [
            'user'  => ['id' => $user->id, 'name' => $user->name, 'email' => $user->email],
            'token' => $user->createToken('api')->plainTextToken,
        ];
    }
}
