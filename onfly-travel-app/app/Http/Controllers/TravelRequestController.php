<?php

namespace App\Http\Controllers;

use App\Http\Requests\ListTravelRequestRequest;
use App\Http\Requests\StoreTravelRequestRequest;
use App\Http\Requests\UpdateTravelRequestRequest;
use App\Http\Resources\TravelRequestResource;
use App\Models\TravelRequest as TravelModel;
use App\Services\TravelRequestService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class TravelRequestController extends Controller
{

    protected TravelRequestService $travelRequestService;

    public function __construct(TravelRequestService $travelRequestService)
    {
        $this->travelRequestService = $travelRequestService;
    }

    public function index(ListTravelRequestRequest $request): JsonResponse | JsonResource
    {
        try {
            $filters = $request->validated();
            $travelRequests = $this->travelRequestService->list($filters);

            return TravelRequestResource::collection($travelRequests);
        } catch (\Exception $e) {
            Log::error("Erro no TravelRequestController@index: " . $e->getMessage());
            return response()->json(['message' => 'Erro interno do servidor ao listar pedidos de viagem.', 'error' => $e->getMessage()], 500);
        }
    }

    public function store(StoreTravelRequestRequest $request): JsonResponse | JsonResource
    {
        try {
            $travelRequest = $this->travelRequestService->create($request->validated());
            return new TravelRequestResource($travelRequest);
        } catch (\Exception $e) {
            Log::error("Erro no TravelRequestController@store: " . $e->getMessage());
            return response()->json(['message' => 'Erro interno do servidor ao criar pedido de viagem.', 'error' => $e->getMessage()], 500);
        }
    }

    public function show(TravelModel $travelRequest): JsonResponse | JsonResource
    {
        try {
            return new TravelRequestResource($travelRequest);
        } catch (\Exception $e) {
            Log::error("Erro no TravelRequestController@show: " . $e->getMessage());
            return response()->json(['message' => 'Erro interno do servidor ao buscar pedido de viagem.', 'error' => $e->getMessage()], 500);
        }
    }

    public function update(UpdateTravelRequestRequest $request, TravelModel $travelRequest): JsonResponse | JsonResource
    {
        try {

            $updatedTravelRequest = $this->travelRequestService->updateStatus(
                $travelRequest,
                $request->validated('status'),
                $request->validated('user_uuid')
            );
            return new TravelRequestResource($updatedTravelRequest);
        } catch (ValidationException $e) {
            return response()->json(['message' => $e->getMessage(), 'errors' => $e->errors()], 422);
        } catch (Exception $e) {
            Log::error("Erro no TravelRequestController@update: " . $e->getMessage());
            return response()->json(['message' => 'Erro interno do servidor ao atualizar pedido de viagem.', 'error' => $e->getMessage()], 500);
        }
    }

    public function destroy(TravelModel $travelRequest): JsonResponse
    {
        try {
            $this->travelRequestService->delete($travelRequest);
            return response()->json(null, 204);
        } catch (Exception $e) {
            Log::error("Erro no TravelRequestController@destroy: " . $e->getMessage());
            return response()->json(['message' => 'Erro interno do servidor ao excluir pedido de viagem.', 'error' => $e->getMessage()], 500);
        }
    }
}
