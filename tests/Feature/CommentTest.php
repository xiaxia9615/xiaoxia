<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Comment\Models\Comment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class CommentTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_comments(): void
    {
        $this->getJson('/api/v1/comments/index?product_id=1')->assertUnauthorized();
    }

    public function test_user_can_view_comments_for_product(): void
    {
        $user = User::factory()->create(['name' => '评论者']);
        Sanctum::actingAs($user);

        $comment = Comment::create([
            'user_id' => $user->id,
            'product_id' => 1,
            'content' => '好评！',
        ]);

        $response = $this->getJson('/api/v1/comments/index?product_id=1');

        $response->assertOk()->assertJsonPath('code', 0);
        $this->assertSame(1, $response->json('data.total'));
        $this->assertSame('评论者', $response->json('data.list.0.user_name'));
    }
}
