<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use App\Modules\Team\Models\Team;
use App\Modules\Team\Models\TeamUser;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TeamUser>
 */
class TeamUserFactory extends Factory
{
    protected $model = TeamUser::class;

    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'user_id' => User::factory(),
            'role'    => fake()->randomElement(['owner', 'admin', 'member']),
        ];
    }

    public function owner(): static
    {
        return $this->state(fn () => ['role' => 'owner']);
    }

    public function admin(): static
    {
        return $this->state(fn () => ['role' => 'admin']);
    }

    public function member(): static
    {
        return $this->state(fn () => ['role' => 'member']);
    }
}
