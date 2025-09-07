<?php

namespace App\Http\Requests;

use App\Models\TravelRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListTravelRequestRequest extends FormRequest
{

    private string $requested;
    private string $approved;
    private string $canceled;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {

        $this->requested = TravelRequest::STATUS_REQUESTED;
        $this->approved = TravelRequest::STATUS_APPROVED;
        $this->canceled = TravelRequest::STATUS_CANCELED;

        return [
            'status' => ['nullable', 'string', Rule::in([
                $this->requested,
                $this->approved,
                $this->canceled,
            ])],
            'destination' => ['nullable', 'string', 'max:255'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.in' => 'O status deve ser um dos seguintes: ' . $this->requested . ', ' . $this->approved . ' ou ' . $this->canceled . '.',
            'destination.string' => 'O destino deve ser um texto.',
            'destination.max' => 'O destino não pode ter mais de :max caracteres.',
            'start_date.date' => 'A data de início deve ser uma data válida.',
            'end_date.date' => 'A data de fim deve ser uma data válida.',
            'end_date.after_or_equal' => 'A data de fim não pode ser anterior à data de início.',
        ];
    }
}
