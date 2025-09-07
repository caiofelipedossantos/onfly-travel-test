<?php

namespace App\Http\Requests;

use App\Models\TravelRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTravelRequestRequest extends FormRequest
{

    private string $approved;
    private string $canceled;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $this->approved = TravelRequest::STATUS_APPROVED;
        $this->canceled = TravelRequest::STATUS_CANCELED;

        return [
            'status' => ['required', 'string', Rule::in([
                $this->approved,
                $this->canceled,
            ])],
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'O status é obrigatório.',
            'status.in' => 'O status deve ser "' . $this->approved . '" ou "' . $this->canceled . '".',
        ];
    }
}
