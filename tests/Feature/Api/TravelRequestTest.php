<?php

namespace Tests\Feature\Api;

use App\Models\TravelRequest;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Str;
use Tests\TestCase;

class TravelRequestTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected string $userUuid;
    protected string $anotherUserUuid;
    protected string $requestorName;

    /**
     * Configuração inicial para cada teste.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->userUuid = Str::uuid()->toString();
        $this->anotherUserUuid = Str::uuid()->toString();
        $this->requestorName = $this->faker->name();
    }

    /**
     * Teste para verificar se um pedido de viagem pode ser criado com o header X-USER-CODE.
     */
    public function test_can_create_travel_request_with_x_user_code_header(): void
    {
        $departureDate = Carbon::tomorrow()->addHours(10)->addMinutes(30);
        $returnDate = Carbon::tomorrow()->addDays(7)->addHours(15)->addMinutes(45);

        $data = [
            'destination' => 'Paris, França',
            'departure_date' => $departureDate->format('Y-m-d H:i'),
            'return_date' => $returnDate->format('Y-m-d H:i'),
            'external_id' => 'EXT-REQ-001',
            'requestor_name' => $this->requestorName, // Incluído no dado de entrada
        ];

        $response = $this->postJson('/api/v1/travel-requests', $data, [
            'X-USER-CODE' => $this->userUuid
        ]);

        $response->assertStatus(201)
            ->assertJsonFragment([
                'destination' => 'Paris, França',
                'status' => TravelRequest::STATUS_REQUESTED,
                'user_code' => $this->userUuid,
                'identify' => $response->json('identify'),
                'order_code' => 'EXT-REQ-001',
                'requestor_name' => $this->requestorName,
                'departure_date' => $departureDate->format('Y-m-d H:i'),
                'return_date' => $returnDate->format('Y-m-d H:i'),
            ]);

        $this->assertDatabaseHas('travel_requests', [
            'destination' => 'Paris, França',
            'user_uuid' => $this->userUuid,
            'status' => TravelRequest::STATUS_REQUESTED,
            'external_id' => 'EXT-REQ-001',
            'requestor_name' => $this->requestorName,
        ]);
    }

    /**
     * Teste para verificar se não é possível criar um pedido de viagem sem o header X-USER-CODE.
     */
    public function test_cannot_create_travel_request_without_x_user_code_header(): void
    {
        $data = [
            'destination' => 'Londres, Reino Unido',
            'departure_date' => Carbon::tomorrow()->format('Y-m-d H:i'),
            'return_date' => Carbon::tomorrow()->addDays(5)->format('Y-m-d H:i'),
            'requestor_name' => $this->requestorName,
        ];

        $response = $this->postJson('/api/v1/travel-requests', $data);

        $response->assertStatus(403);
    }

    /**
     * Teste para verificar se não é possível criar um pedido de viagem com dados inválidos.
     */
    public function test_cannot_create_travel_request_with_invalid_data(): void
    {
        $invalidData = [
            'destination' => '',
            'departure_date' => Carbon::yesterday()->format('Y-m-d H:i'),
            'return_date' => Carbon::today()->format('Y-m-d H:i'),
            'requestor_name' => $this->requestorName,
        ];

        $response = $this->postJson('/api/v1/travel-requests', $invalidData, [
            'X-USER-CODE' => $this->userUuid
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['destination', 'departure_date', 'return_date']);
    }

    /**
     * Teste para listar todos os pedidos de viagem.
     */
    public function test_can_list_all_travel_requests(): void
    {
        TravelRequest::factory()->count(5)->create(['user_uuid' => $this->userUuid, 'requestor_name' => $this->requestorName]);
        TravelRequest::factory()->count(2)->create(['user_uuid' => $this->anotherUserUuid, 'requestor_name' => $this->faker->name()]);

        $response = $this->getJson('/api/v1/travel-requests', [
            'X-USER-CODE' => $this->userUuid
        ]);

        $response->assertStatus(200)->assertJsonCount(7);
        $response->assertJsonStructure([
            '*' => [
                'identify',
                'user_code',
                'order_code',
                'requestor_name',
                'destination',
                'departure_date',
                'return_date',
                'status',
                'created_at',
                'updated_at',
            ]
        ]);

        if (!empty($response->json())) {
            $firstItem = $response->json()[0];
            $this->assertMatchesRegularExpression('/\d{4}-\d{2}-\d{2} \d{2}:\d{2}/', $firstItem['departure_date']);
            $this->assertMatchesRegularExpression('/\d{4}-\d{2}-\d{2} \d{2}:\d{2}/', $firstItem['return_date']);
        }
    }

    /**
     * Teste para listar pedidos de viagem filtrados por status.
     */
    public function test_can_filter_travel_requests_by_status(): void
    {
        TravelRequest::factory()->count(3)->create(['status' => TravelRequest::STATUS_REQUESTED, 'user_uuid' => $this->userUuid, 'requestor_name' => $this->requestorName]);
        TravelRequest::factory()->count(2)->create(['status' => TravelRequest::STATUS_APPROVED, 'user_uuid' => $this->userUuid, 'requestor_name' => $this->requestorName]);
        TravelRequest::factory()->count(1)->create(['status' => TravelRequest::STATUS_CANCELED, 'user_uuid' => $this->userUuid, 'requestor_name' => $this->requestorName]);

        $response = $this->getJson('/api/v1/travel-requests?status=' . TravelRequest::STATUS_APPROVED, [
            'X-USER-CODE' => $this->userUuid
        ]);

        $response->assertStatus(200)->assertJsonCount(2);
        $this->assertEquals(TravelRequest::STATUS_APPROVED, $response->json()[0]['status']);
        $this->assertEquals($this->userUuid, $response->json()[0]['user_code']);
        $this->assertEquals($this->requestorName, $response->json()[0]['requestor_name']);
    }

    /**
     * Teste para listar pedidos de viagem filtrados por destino.
     */
    public function test_can_filter_travel_requests_by_destination(): void
    {
        TravelRequest::factory()->create(['destination' => 'Paris', 'user_uuid' => $this->userUuid, 'requestor_name' => $this->requestorName]);
        TravelRequest::factory()->create(['destination' => 'Paris, França', 'user_uuid' => $this->userUuid, 'requestor_name' => $this->requestorName]);
        TravelRequest::factory()->create(['destination' => 'Berlim', 'user_uuid' => $this->userUuid, 'requestor_name' => $this->requestorName]);

        $response = $this->getJson('/api/v1/travel-requests?destination=Paris', [
            'X-USER-CODE' => $this->userUuid
        ]);

        $response->assertStatus(200)
            ->assertJsonCount(2);
        $this->assertStringContainsStringIgnoringCase('paris', $response->json()[0]['destination']);
        $this->assertStringContainsStringIgnoringCase('paris', $response->json()[1]['destination']);
    }

    /**
     * Teste para listar pedidos de viagem filtrados por período de datas.
     */
    public function test_can_filter_travel_requests_by_date_range(): void
    {
        // Pedido dentro do range
        TravelRequest::factory()->create([
            'departure_date' => Carbon::today()->addDays(5),
            'return_date' => Carbon::today()->addDays(10),
            'user_uuid' => $this->userUuid,
            'requestor_name' => $this->requestorName
        ]);
        // Pedido com departure_date no range, return_date fora
        TravelRequest::factory()->create([
            'departure_date' => Carbon::today()->addDays(1),
            'return_date' => Carbon::today()->addDays(15),
            'user_uuid' => $this->userUuid,
            'requestor_name' => $this->requestorName
        ]);
        // Pedido com return_date no range, departure_date fora
        TravelRequest::factory()->create([
            'departure_date' => Carbon::today()->subDays(5),
            'return_date' => Carbon::today()->addDays(3),
            'user_uuid' => $this->userUuid,
            'requestor_name' => $this->requestorName
        ]);
        // Pedido fora do range
        TravelRequest::factory()->create([
            'departure_date' => Carbon::today()->addDays(20),
            'return_date' => Carbon::today()->addDays(25),
            'user_uuid' => $this->userUuid,
            'requestor_name' => $this->requestorName
        ]);

        $startDate = Carbon::today()->format('Y-m-d');
        $endDate = Carbon::today()->addDays(10)->format('Y-m-d');

        $response = $this->getJson("/api/v1/travel-requests?start_date={$startDate}&end_date={$endDate}", [
            'X-USER-CODE' => $this->userUuid
        ]);

        $response->assertStatus(200)
            ->assertJsonCount(3);
    }

    /**
     * Teste para verificar se não é possível listar pedidos sem o header X-USER-CODE.
     */
    public function test_cannot_list_travel_requests_without_x_user_code_header(): void
    {
        TravelRequest::factory()->count(1)->create();
        $response = $this->getJson('/api/v1/travel-requests');
        $response->assertStatus(403);
    }

    /**
     * Teste para verificar se é possível obter um único pedido de viagem.
     */
    public function test_can_get_single_travel_request(): void
    {
        $travelRequest = TravelRequest::factory()->create(['user_uuid' => $this->userUuid, 'requestor_name' => $this->requestorName]);

        $response = $this->getJson('/api/v1/travel-requests/' . $travelRequest->uuid, [
            'X-USER-CODE' => $this->userUuid
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'identify' => $travelRequest->uuid,
                'destination' => $travelRequest->destination,
                'status' => $travelRequest->status,
                'user_code' => $travelRequest->user_uuid,
                'order_code' => $travelRequest->external_id,
                'requestor_name' => $travelRequest->requestor_name,
                'departure_date' => $travelRequest->departure_date->format('Y-m-d H:i'),
                'return_date' => $travelRequest->return_date->format('Y-m-d H:i'),
                'created_at' => $travelRequest->created_at->format('Y-m-d H:i'),
                'updated_at' => $travelRequest->updated_at->format('Y-m-d H:i'),
            ]);
    }

    /**
     * Teste para verificar erro ao tentar obter um pedido de viagem inexistente.
     */
    public function test_error_get_single_travel_request(): void
    {
        $nonExistentUuid = Str::uuid()->toString();
        $response = $this->getJson('/api/v1/travel-requests/' . $nonExistentUuid, [
            'X-USER-CODE' => $this->userUuid
        ]);
        $response->assertStatus(404);
    }

    /**
     * Teste para verificar se não é possível obter um único pedido sem o header X-USER-CODE.
     */
    public function test_cannot_get_single_travel_request_without_x_user_code_header(): void
    {
        $travelRequest = TravelRequest::factory()->create();
        $response = $this->getJson('/api/v1/travel-requests/' . $travelRequest->uuid);
        $response->assertStatus(403);
    }

    /**
     * Teste para verificar se um usuário diferente pode aprovar um pedido de viagem.
     */
    public function test_another_user_can_approve_travel_request_status(): void
    {
        $travelRequest = TravelRequest::factory()->create([
            'status' => TravelRequest::STATUS_REQUESTED,
            'user_uuid' => $this->userUuid,
            'requestor_name' => $this->requestorName,
        ]);

        $response = $this->putJson(
            '/api/v1/travel-requests/' . $travelRequest->uuid,
            ['status' => TravelRequest::STATUS_APPROVED],
            ['X-USER-CODE' => $this->anotherUserUuid]
        );

        $response->assertStatus(200)
            ->assertJsonFragment([
                'status' => TravelRequest::STATUS_APPROVED,
                'identify' => $travelRequest->uuid,
                'user_code' => $this->userUuid,
                'requestor_name' => $this->requestorName,
            ]);

        $this->assertDatabaseHas('travel_requests', [
            'uuid' => $travelRequest->uuid,
            'status' => TravelRequest::STATUS_APPROVED
        ]);
    }

    /**
     * Teste para verificar se um usuário diferente pode cancelar um pedido de viagem.
     */
    public function test_another_user_can_cancel_travel_request_status(): void
    {
        $travelRequest = TravelRequest::factory()->create([
            'status' => TravelRequest::STATUS_REQUESTED,
            'user_uuid' => $this->userUuid,
            'requestor_name' => $this->requestorName,
        ]);

        $response = $this->putJson(
            '/api/v1/travel-requests/' . $travelRequest->uuid,
            ['status' => TravelRequest::STATUS_CANCELED],
            ['X-USER-CODE' => $this->anotherUserUuid]
        );

        $response->assertStatus(200)
            ->assertJsonFragment([
                'status' => TravelRequest::STATUS_CANCELED,
                'identify' => $travelRequest->uuid,
                'user_code' => $this->userUuid,
                'requestor_name' => $this->requestorName,
            ]);

        $this->assertDatabaseHas('travel_requests', [
            'uuid' => $travelRequest->uuid,
            'status' => TravelRequest::STATUS_CANCELED
        ]);
    }

    /**
     * Teste para verificar se o usuário que fez o pedido NÃO pode alterar o status do próprio pedido.
     */
    public function test_user_cannot_update_their_own_travel_request_status(): void
    {
        $travelRequest = TravelRequest::factory()->create([
            'status' => TravelRequest::STATUS_REQUESTED,
            'user_uuid' => $this->userUuid,
            'requestor_name' => $this->requestorName,
        ]);

        $response = $this->putJson(
            '/api/v1/travel-requests/' . $travelRequest->uuid,
            ['status' => TravelRequest::STATUS_APPROVED],
            ['X-USER-CODE' => $this->userUuid]
        );

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status'])
            ->assertJsonFragment(['message' => 'Você não tem permissão para alterar o status do seu próprio pedido.']);

        $this->assertDatabaseHas('travel_requests', [
            'uuid' => $travelRequest->uuid,
            'status' => TravelRequest::STATUS_REQUESTED
        ]);
    }

    /**
     * Teste para verificar se não é possível atualizar para um status inválido.
     */
    public function test_cannot_update_travel_request_status_to_invalid_status(): void
    {
        $travelRequest = TravelRequest::factory()->create([
            'status' => TravelRequest::STATUS_REQUESTED,
            'user_uuid' => $this->userUuid,
            'requestor_name' => $this->requestorName,
        ]);

        $response = $this->putJson(
            '/api/v1/travel-requests/' . $travelRequest->uuid,
            ['status' => 'invalid_status_here'],
            ['X-USER-CODE' => $this->anotherUserUuid]
        );

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    /**
     * Teste para verificar se não é possível alterar o status de um pedido já cancelado.
     */
    public function test_cannot_update_already_canceled_travel_request_status(): void
    {
        $travelRequest = TravelRequest::factory()->create([
            'status' => TravelRequest::STATUS_CANCELED,
            'user_uuid' => $this->userUuid,
            'requestor_name' => $this->requestorName,
        ]);

        $response = $this->putJson(
            '/api/v1/travel-requests/' . $travelRequest->uuid,
            ['status' => TravelRequest::STATUS_APPROVED],
            ['X-USER-CODE' => $this->anotherUserUuid]
        );

        $response->assertStatus(422)
            ->assertJsonFragment(['message' => 'Não é possível alterar o status de um pedido já cancelado.']);

        $this->assertDatabaseHas('travel_requests', [
            'uuid' => $travelRequest->uuid,
            'status' => TravelRequest::STATUS_CANCELED
        ]);
    }

    /**
     * Teste para verificar se não é possível cancelar um pedido aprovado com data de partida no passado.
     */
    public function test_cannot_cancel_approved_travel_request_with_past_departure_date(): void
    {
        $travelRequest = TravelRequest::factory()->create([
            'status' => TravelRequest::STATUS_APPROVED,
            'departure_date' => Carbon::yesterday(),
            'return_date' => Carbon::today(),
            'user_uuid' => $this->userUuid,
            'requestor_name' => $this->requestorName,
        ]);

        $response = $this->putJson(
            '/api/v1/travel-requests/' . $travelRequest->uuid,
            ['status' => TravelRequest::STATUS_CANCELED],
            ['X-USER-CODE' => $this->anotherUserUuid]
        );

        $response->assertStatus(422)
            ->assertJsonFragment(['message' => 'Não é possível cancelar um pedido de viagem aprovado com data de partida no passado.']);

        $this->assertDatabaseHas('travel_requests', [
            'uuid' => $travelRequest->uuid,
            'status' => TravelRequest::STATUS_APPROVED
        ]);
    }

    /**
     * Teste para verificar se É possível cancelar um pedido aprovado com data de partida no futuro.
     */
    public function test_can_cancel_approved_travel_request_with_future_departure_date(): void
    {
        $travelRequest = TravelRequest::factory()->create([
            'status' => TravelRequest::STATUS_APPROVED,
            'departure_date' => Carbon::tomorrow(),
            'return_date' => Carbon::tomorrow()->addDays(5),
            'user_uuid' => $this->userUuid,
            'requestor_name' => $this->requestorName,
        ]);

        $response = $this->putJson(
            '/api/v1/travel-requests/' . $travelRequest->uuid,
            ['status' => TravelRequest::STATUS_CANCELED],
            ['X-USER-CODE' => $this->anotherUserUuid]
        );

        $response->assertStatus(200)
            ->assertJsonFragment([
                'status' => TravelRequest::STATUS_CANCELED,
                'identify' => $travelRequest->uuid,
                'user_code' => $this->userUuid,
                'requestor_name' => $this->requestorName,
            ]);

        $this->assertDatabaseHas('travel_requests', [
            'uuid' => $travelRequest->uuid,
            'status' => TravelRequest::STATUS_CANCELED
        ]);
    }

    /**
     * Teste para verificar se não é possível atualizar o status de um pedido sem o header X-USER-CODE.
     */
    public function test_cannot_update_travel_request_status_without_x_user_code_header(): void
    {
        $travelRequest = TravelRequest::factory()->create(['status' => TravelRequest::STATUS_REQUESTED]);

        $response = $this->putJson('/api/v1/travel-requests/' . $travelRequest->uuid, [
            'status' => TravelRequest::STATUS_APPROVED
        ]);

        $response->assertStatus(403);
    }

    /**
     * Teste para verificar se um pedido de viagem pode ser soft-deletado.
     */
    public function test_can_soft_delete_travel_request(): void
    {
        $travelRequest = TravelRequest::factory()->create(['user_uuid' => $this->userUuid, 'requestor_name' => $this->requestorName]);

        $response = $this->deleteJson('/api/v1/travel-requests/' . $travelRequest->uuid, [], [
            'X-USER-CODE' => $this->userUuid
        ]);

        $response->assertStatus(204);

        $this->assertSoftDeleted('travel_requests', ['uuid' => $travelRequest->uuid]);
        $this->assertDatabaseMissing('travel_requests', ['uuid' => $travelRequest->uuid]);
    }

    /**
     * Teste para verificar erro ao tentar soft-deletar um pedido de viagem inexistente.
     */
    public function test_cannot_delete_non_existent_travel_request(): void
    {
        $nonExistentUuid = Str::uuid()->toString();

        $response = $this->deleteJson('/api/v1/travel-requests/' . $nonExistentUuid, [], [
            'X-USER-CODE' => $this->userUuid
        ]);

        $response->assertStatus(404);
    }
}
