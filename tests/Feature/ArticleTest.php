<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Modules\Article\Models\Article;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ArticleTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_list_articles(): void
    {
        Article::create(['title' => '文章一', 'content' => '内容一', 'category' => '技术']);
        Article::create(['title' => '文章二', 'content' => '内容二', 'category' => '生活']);

        $response = $this->getJson('/api/v1/article/index');

        $response->assertOk()->assertJsonPath('code', 0);
        $this->assertSame(2, $response->json('data.total'));
    }

    public function test_guest_can_view_article_detail(): void
    {
        $article = Article::create(['title' => '详情测试', 'content' => '详情内容', 'category' => '新闻']);

        $response = $this->getJson('/api/v1/article/detail?id='.$article->id);

        $response->assertOk()->assertJsonPath('data.title', '详情测试');
    }

    public function test_article_detail_returns_404_for_missing(): void
    {
        $this->getJson('/api/v1/article/detail?id=99999')->assertNotFound();
    }

    public function test_guest_can_list_categories(): void
    {
        Article::create(['title' => 'A', 'content' => 'a', 'category' => '技术']);
        Article::create(['title' => 'B', 'content' => 'b', 'category' => '生活']);
        Article::create(['title' => 'C', 'content' => 'c', 'category' => '技术']);

        $response = $this->getJson('/api/v1/article/category');

        $response->assertOk()->assertJsonPath('code', 0);
        $categories = $response->json('data.list');
        $this->assertContains('技术', $categories);
        $this->assertContains('生活', $categories);
        $this->assertSame(2, count(array_unique($categories)));
    }
}
