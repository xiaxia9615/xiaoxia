<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Message\Models\Message;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class MessageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_message_apis(): void
    {
        $this->getJson('/api/v1/message/index')->assertUnauthorized();
        $this->getJson('/api/v1/message/unread')->assertUnauthorized();
        $this->postJson('/api/v1/message/read', ['id' => 1])->assertUnauthorized();
    }

    public function test_user_can_view_own_messages(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        Message::create([
            'user_id' => $user->id,
            'title' => '系统通知',
            'content' => '欢迎使用',
            'is_read' => false,
        ]);

        $response = $this->getJson('/api/v1/message/index');

        $response->assertOk()->assertJsonPath('code', 0);
        $this->assertSame(1, $response->json('data.total'));
    }

    public function test_unread_count(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        Message::create(['user_id' => $user->id, 'title' => 'A', 'content' => 'a', 'is_read' => false]);
        Message::create(['user_id' => $user->id, 'title' => 'B', 'content' => 'b', 'is_read' => false]);
        Message::create(['user_id' => $user->id, 'title' => 'C', 'content' => 'c', 'is_read' => true]);

        $response = $this->getJson('/api/v1/message/unread');

        $response->assertOk()->assertJsonPath('data.count', 2);
    }

    public function test_user_can_mark_own_message_read(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $message = Message::create([
            'user_id' => $user->id,
            'title' => 'T',
            'content' => 'c',
            'is_read' => false,
        ]);

        $this->postJson('/api/v1/message/read', ['id' => $message->id])
            ->assertOk()
            ->assertJsonPath('code', 0);

        $this->assertSame(true, $message->refresh()->is_read);
    }

    public function test_user_cannot_mark_others_message_read(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();

        $message = Message::create([
            'user_id' => $owner->id,
            'title' => 'T',
            'content' => 'c',
            'is_read' => false,
        ]);

        Sanctum::actingAs($other);

        $this->postJson('/api/v1/message/read', ['id' => $message->id])->assertNotFound();
        $this->assertSame(false, $message->refresh()->is_read);
    }
}
