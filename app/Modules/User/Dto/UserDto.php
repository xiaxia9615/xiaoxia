<?php

declare(strict_types=1);

namespace App\Modules\User\Dto;

use App\Modules\User\Models\User;

final readonly class UserDto
{
    public function __construct(
        public int $id,
        public string $name,
        public string $email,
    ) {}

    public static function fromModel(User $user): self
    {
        return new self(
            id: $user->id,
            name: $user->name,
            email: $user->email,
        );
    }
}
