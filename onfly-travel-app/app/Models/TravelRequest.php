<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TravelRequest extends Model
{
    use HasFactory, SoftDeletes, HasUuid;

    /**
     * Atributos que podem ser atribuídos em massa.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',
        'user_uuid',
        'external_id',
        'requestor_name',
        'destination',
        'departure_date',
        'return_date',
        'status',
    ];

    /**
     * Atributos que devem ser convertidos para tipos nativos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'departure_date' => 'datetime',
        'return_date'    => 'datetime',
        'status'         => 'string',
    ];

    /**
     * Enum de status disponíveis.
     */
    public const STATUS_REQUESTED = 'requested';
    public const STATUS_APPROVED  = 'approved';
    public const STATUS_CANCELED  = 'canceled';

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
