<?php

namespace App\Services;

use App\Models\TravelRequest;
use Exception;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class TravelRequestService
{
    protected TravelRequest $travelRequest;

    public function __construct(TravelRequest $travelRequest)
    {
        $this->travelRequest = $travelRequest;
    }

    public function create(array $data): TravelRequest
    {
        if (!isset($data['user_id']) || empty($data['user_id'])) {
            throw new Exception('O identificador do usuário do solicitante é necessário para criar um pedido de viagem.');
        }

        $data['status'] = $data['status'] ?? TravelRequest::STATUS_REQUESTED;

        DB::beginTransaction();
        try {
            $travelRequest = $this->travelRequest->create($data);
            DB::commit();
            return $travelRequest;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Erro ao criar pedido de viagem: " . $e->getMessage(), ['data' => $data]);
            throw new Exception('Não foi possível criar o pedido de viagem. Tente novamente mais tarde.');
        }
    }

    public function updateStatus(TravelRequest $travelRequest, string $newStatus, int $responsibleUserId): TravelRequest
    {
        if ($responsibleUserId === $travelRequest->user_id) {
            throw ValidationException::withMessages([
                'status' => 'Você não tem permissão para alterar o status do seu próprio pedido.',
            ]);
        }

        if (!in_array($newStatus, [TravelRequest::STATUS_APPROVED, TravelRequest::STATUS_CANCELED])) {
            throw ValidationException::withMessages([
                'status' => 'Status inválido. Apenas "approved" ou "canceled" são permitidos.',
            ]);
        }

        if ($travelRequest->status === $newStatus) {
            throw ValidationException::withMessages([
                'status' => "O pedido já está com o status '{$newStatus}'.",
            ]);
        }

        if ($travelRequest->status === TravelRequest::STATUS_CANCELED) {
            throw ValidationException::withMessages([
                'status' => 'Não é possível alterar o status de um pedido já cancelado.',
            ]);
        }

        if ($newStatus === TravelRequest::STATUS_CANCELED && $travelRequest->status === TravelRequest::STATUS_APPROVED) {
            if (Carbon::parse($travelRequest->departure_date)->isPast()) {
                throw ValidationException::withMessages([
                    'cancellation' => 'Não é possível cancelar um pedido aprovado com data de partida no passado.',
                ]);
            }
        }

        DB::beginTransaction();
        try {
            $travelRequest->status = $newStatus;
            $travelRequest->save();

            DB::commit();
            return $travelRequest;
        } catch (ValidationException $e) {
            DB::rollBack();
            throw $e;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Erro ao atualizar status do pedido de viagem: " . $e->getMessage(), [
                'travel_request_id' => $travelRequest->id,
                'new_status' => $newStatus
            ]);
            throw new Exception('Não foi possível atualizar o status do pedido de viagem. Tente novamente mais tarde.');
        }
    }

    public function find(string $uuid): ?TravelRequest
    {
        return $this->travelRequest->where('uuid', $uuid)->first();
    }

    public function list(array $filters, int $userId): LengthAwarePaginator
    {
        $query = $this->travelRequest->query();

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['destination'])) {
            $query->where('destination', 'like', '%' . $filters['destination'] . '%');
        }

        if (!empty($filters['start_date'])) {
            $query->where(function ($q) use ($filters) {
                $q->whereDate('departure_date', '>=', $filters['start_date'])
                    ->orWhereDate('return_date', '>=', $filters['start_date']);
            });
        }

        if (!empty($filters['end_date'])) {
            $query->where(function ($q) use ($filters) {
                $q->whereDate('departure_date', '<=', $filters['end_date'])
                    ->orWhereDate('return_date', '<=', $filters['end_date']);
            });
        }

        return $query->where('user_id', $userId)->paginate();
    }

    public function delete(TravelRequest $travelRequest, int $userId): ?bool
    {
        if ($userId === $travelRequest->user_id) {
            throw ValidationException::withMessages([
                'status' => 'Você não tem permissão para deletar seu próprio pedido.',
            ]);
        }

        DB::beginTransaction();
        try {
            $result = $travelRequest->delete();
            DB::commit();
            return $result;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Erro ao excluir pedido de viagem: " . $e->getMessage(), [
                'travel_request_id' => $travelRequest->id
            ]);
            throw new Exception('Não foi possível excluir o pedido de viagem. Tente novamente mais tarde.');
        }
    }
}
