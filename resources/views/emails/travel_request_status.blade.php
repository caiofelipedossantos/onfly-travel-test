<x-mail::message>
# Pedido de Viagem {{ ucfirst($action) }}

Olá {{ $travelRequest->requestor_name }},

O seu pedido de viagem para **{{ $travelRequest->destination }}** foi **{{ $action }}**.

- Código do pedido: {{ $travelRequest->external_id }}
- Data de partida: {{ $travelRequest->departure_date->format('d/m/Y H:i') }}
- Data de retorno: {{ $travelRequest->return_date->format('d/m/Y H:i') }}
- Status atual: {{ $travelRequest->status }}

@component('mail::button', ['url' => url('/travel-requests/'.$travelRequest->uuid)])
Ver detalhes do pedido
@endcomponent

Obrigado,<br>
{{ config('app.name') }}
</x-mail::message>
