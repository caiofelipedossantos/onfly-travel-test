<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ListTravelRequestRequest;
use App\Http\Requests\StoreTravelRequestRequest;
use App\Http\Requests\UpdateTravelRequestRequest;
use App\Http\Resources\TravelRequestResource;
use App\Models\TravelRequest;
use App\Services\TravelRequestService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class TravelRequestController extends Controller
{
    use AuthorizesRequests;

    protected TravelRequestService $travelRequestService;

    public function __construct(TravelRequestService $travelRequestService)
    {
        $this->travelRequestService = $travelRequestService;
    }

    public function index(ListTravelRequestRequest $request): JsonResponse | JsonResource
    {
        try {
            $user = auth()->user();
            $filters = $request->validated();
            $travelRequests = $this->travelRequestService->list($filters, $user->id);

            return TravelRequestResource::collection($travelRequests);
        } catch (\Exception $e) {
            Log::error("Erro no TravelRequestController@index: " . $e->getMessage());
            return response()->json([
                'message' => 'Erro interno do servidor ao listar pedidos de viagem.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(StoreTravelRequestRequest $request): JsonResponse | JsonResource
    {
        try {
            $user = auth()->user();
            $data = $request->validated();
            $data['user_id'] = $user->id;

            $travelRequest = $this->travelRequestService->create($data);
            return new TravelRequestResource($travelRequest);
        } catch (\Exception $e) {
            Log::error("Erro no TravelRequestController@store: " . $e->getMessage());
            return response()->json([
                'message' => 'Erro interno do servidor ao criar pedido de viagem.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show(TravelRequest $travelRequest): JsonResponse | JsonResource
    {
        try {
            $this->authorize('view', $travelRequest);

            return new TravelRequestResource($travelRequest);
        } catch (\Exception $e) {
            Log::error("Erro no TravelRequestController@show: " . $e->getMessage());
            return response()->json([
                'message' => 'Erro ao buscar pedido de viagem.',
                'error' => $e->getMessage()
            ], 403);
        }
    }

    public function update(UpdateTravelRequestRequest $request, TravelRequest $travelRequest): JsonResponse | JsonResource
    {
        try {
            $this->authorize('changeStatus', $travelRequest);

            $status = $request->validated('status');
            $updatedTravelRequest = $this->travelRequestService->updateStatus($travelRequest, $status, auth()->user()->id);

            return new TravelRequestResource($updatedTravelRequest);
        } catch (ValidationException $e) {
            return response()->json(['message' => $e->getMessage(), 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error("Erro no TravelRequestController@update: " . $e->getMessage());
            return response()->json([
                'message' => 'Erro interno do servidor ao atualizar pedido de viagem.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(TravelRequest $travelRequest): JsonResponse
    {
        try {
            $this->authorize('delete', $travelRequest);

            $this->travelRequestService->delete($travelRequest, auth()->user()->id);
            return response()->json(null, 204);
        } catch (\Exception $e) {
            Log::error("Erro no TravelRequestController@destroy: " . $e->getMessage());
            return response()->json([
                'message' => 'Erro interno do servidor ao excluir pedido de viagem.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
