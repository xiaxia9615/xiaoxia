<?php
namespace Tests\Feature\Distributor;
use App\Models\User;
use App\Modules\Distributor\Models\DistributorRelation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DistributorTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_bind_distributor()
    {
        Http::fake();
        $user = User::factory()->create();
        $distributor = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/distributor/bind', ['distributor_id' => $distributor->id])
            ->assertOk();
            
        $this->assertDatabaseHas('distributor_relations', ['user_id' => $user->id, 'distributor_id' => $distributor->id]);
    }

    public function test_cannot_bind_self_or_twice()
    {
        Http::fake();
        $user = User::factory()->create();
        $dist1 = User::factory()->create();
        $dist2 = User::factory()->create();

        // 不能绑自己
        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/distributor/bind', ['distributor_id' => $user->id])
            ->assertStatus(400);

        // 正常绑定
        DistributorRelation::create(['user_id' => $user->id, 'distributor_id' => $dist1->id]);

        // 不能重复绑
        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/distributor/bind', ['distributor_id' => $dist2->id])
            ->assertStatus(400);
    }
}
