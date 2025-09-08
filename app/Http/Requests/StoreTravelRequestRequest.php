<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTravelRequestRequest extends FormRequest
{
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
        return [
            'user_id'           => ['required', 'integer', 'exists:users,id'],
            'external_id'       => ['required', 'string', 'max:255', 'unique:travel_requests,external_id'],
            'requestor_name'    => ['required', 'string', 'max:255'],
            'destination'       => ['required', 'string', 'max:255'],
            'departure_date'    => ['required', 'date', 'after_or_equal:today'],
            'return_date'       => ['required', 'date', 'after_or_equal:departure_date'],
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.required' => 'O ID do usuário é obrigatório.',
            'user_id.integer' => 'O ID do usuário deve ser um número inteiro.',
            'user_id.exists' => 'O ID do usuário deve existir na tabela de usuários.',

            'external_id.required' => 'O ID do pedido é obrigatório.',
            'external_id.unique' => 'O ID do pedido já está em uso.',
            'external_id.max' => 'O ID externo não pode ter mais de :max caracteres.',

            'requestor_name.required' => 'O nome do solicitante é obrigatório.',
            'requestor_name.string' => 'O nome do solicitante deve ser um texto.',
            'requestor_name.max' => 'O nome do solicitante não pode ter mais de :max caracteres.',

            'destination.required' => 'O destino é obrigatório.',
            'destination.string' => 'O destino deve ser um texto.',
            'destination.max' => 'O destino não pode ter mais de :max caracteres.',

            'departure_date.required' => 'A data de ida é obrigatória.',
            'departure_date.date' => 'A data de ida deve ser uma data válida.',
            'departure_date.after_or_equal' => 'A data de ida não pode ser anterior à data de hoje.',

            'return_date.required' => 'A data de volta é obrigatória.',
            'return_date.date' => 'A data de volta deve ser uma data válida.',
            'return_date.after_or_equal' => 'A data de volta não pode ser anterior à data de ida.',
        ];
    }
}
