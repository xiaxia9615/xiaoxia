<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_the_application_returns_a_successful_response(): void
    {
        // 根路由已被 React SPA 接管，返回 200 即可
        $response = $this->get('/');
        $response->assertStatus(200);
    }
}
