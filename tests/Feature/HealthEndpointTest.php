<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class HealthEndpointTest extends TestCase
{
    public function test_health_returns_ok_when_dependencies_up(): void
    {
        $response = $this->getJson('/api/health');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'ok',
                'checks' => [
                    'db'    => true,
                    'cache' => true,
                ],
            ]);
    }

    public function test_health_returns_503_when_db_down(): void
    {
        DB::shouldReceive('select')
            ->andThrow(new \RuntimeException('db down'));

        $response = $this->getJson('/api/health');

        $response->assertStatus(503)
            ->assertJson([
                'status' => 'down',
                'checks' => [
                    'db' => false,
                ],
            ]);
    }
}
