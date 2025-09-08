<?php

namespace Tests\Feature\Api;

use App\Models\TravelRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class TravelRequestFeatureTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;
    protected User $anotherUser;
    protected string $requestorName;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->anotherUser = User::factory()->create();
        $this->requestorName = $this->faker->name();
    }

    /** Testa criação de pedido com sucesso */
    public function test_authenticated_user_can_create_travel_request(): void
    {
        $data = [
            'user_id' => $this->user->id,
            'destination' => 'Nova York',
            'departure_date' => Carbon::tomorrow()->format('Y-m-d H:i'),
            'return_date' => Carbon::tomorrow()->addDays(5)->format('Y-m-d H:i'),
            'requestor_name' => $this->requestorName,
            'external_id' => 'EXT-123'
        ];

        $response = $this->actingAs($this->user, 'api')
                         ->postJson('/api/v1/travel-requests', $data);

        $response->assertStatus(201)
                 ->assertJsonFragment([
                     'destination' => 'Nova York',
                     'requestor_name' => $this->requestorName,
                     'status' => TravelRequest::STATUS_REQUESTED
                 ]);

        $this->assertDatabaseHas('travel_requests', [
            'destination' => 'Nova York',
            'user_id' => $this->user->id,
            'external_id' => 'EXT-123'
        ]);
    }

    /** Testa criação de pedido sem autenticação */
    public function test_guest_cannot_create_travel_request(): void
    {
        $data = [
            'user_id' => $this->user->id,
            'destination' => 'Londres',
            'departure_date' => Carbon::tomorrow()->format('Y-m-d H:i'),
            'return_date' => Carbon::tomorrow()->addDays(3)->format('Y-m-d H:i'),
            'requestor_name' => $this->requestorName
        ];

        $response = $this->postJson('/api/v1/travel-requests', $data);

        $response->assertStatus(401);
    }

    /** Testa listagem de pedidos do usuário */
    public function test_user_can_list_own_travel_requests(): void
    {
        TravelRequest::factory()->count(3)->create(['user_id' => $this->user->id]);
        TravelRequest::factory()->count(2)->create(['user_id' => $this->anotherUser->id]);

        $response = $this->actingAs($this->user, 'api')
                         ->getJson('/api/v1/travel-requests');

        $response->assertStatus(200)
                 ->assertJsonCount(3, 'data');
    }

    /** Testa aprovação de pedido por outro usuário */
    public function test_another_user_can_approve_request(): void
    {
        $request = TravelRequest::factory()->create([
            'user_id' => $this->user->id,
            'status' => TravelRequest::STATUS_REQUESTED
        ]);

        $response = $this->actingAs($this->anotherUser, 'api')
                         ->putJson("/api/v1/travel-requests/{$request->uuid}", [
                             'status' => TravelRequest::STATUS_APPROVED
                         ]);

        $response->assertStatus(200)
                 ->assertJsonFragment(['status' => TravelRequest::STATUS_APPROVED]);

        $this->assertDatabaseHas('travel_requests', [
            'uuid' => $request->uuid,
            'status' => TravelRequest::STATUS_APPROVED
        ]);
    }

    public function test_cannot_cancel_approved_request_with_past_departure(): void
    {
        $request = TravelRequest::factory()->create([
            'user_id' => $this->user->id,
            'status' => TravelRequest::STATUS_APPROVED,
            'departure_date' => Carbon::yesterday(),
            'return_date' => Carbon::tomorrow()->addDay()
        ]);

        $response = $this->actingAs($this->anotherUser, 'api')
                         ->putJson("/api/v1/travel-requests/{$request->uuid}", [
                             'status' => TravelRequest::STATUS_CANCELED
                         ]);
        $response->assertStatus(422);
    }

    /** Testa exclusão de pedido */
    public function test_user_can_delete_travel_request(): void
    {
        $request = TravelRequest::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user, 'api')->delete("/api/v1/travel-requests/{$request->uuid}");

        $response->assertStatus(204);
    }

    /** Testa filtros de listagem */
    public function test_can_filter_travel_requests_by_status(): void
    {
        TravelRequest::factory()->create(['status' => TravelRequest::STATUS_REQUESTED, 'user_id' => $this->user->id]);
        TravelRequest::factory()->create(['status' => TravelRequest::STATUS_APPROVED, 'user_id' => $this->user->id]);

        $response = $this->actingAs($this->user, 'api')
                         ->getJson('/api/v1/travel-requests?status=' . TravelRequest::STATUS_APPROVED);

        $response->assertStatus(200)
                 ->assertJsonCount(1, 'data');
        $this->assertEquals(TravelRequest::STATUS_APPROVED, $response->json('data')[0]['status']);
    }
}
