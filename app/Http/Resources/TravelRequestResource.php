<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TravelRequestResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            "identify"          => $this->uuid,
            "user_code"         => $this->user_uuid,
            "order_code"        => $this->external_id,
            "requestor_name"    => $this->requestor_name,
            "destination"       => $this->destination,
            "departure_date"    => $this->departure_date?->format('Y-m-d H:i'),
            "return_date"       => $this->return_date?->format('Y-m-d H:i'),
            "status"            => $this->status,
            "created_at"        => $this->created_at->format('Y-m-d H:i'),
            "updated_at"        => $this->updated_at->format('Y-m-d H:i'),
        ];
    }
}
