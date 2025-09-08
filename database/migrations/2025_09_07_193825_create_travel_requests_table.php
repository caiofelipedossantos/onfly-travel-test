<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('travel_requests', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->foreignId('user_id')->constrained('users')->comment('ID do usuário que fez o pedido.');
            $table->string('external_id')->unique()->index()->comment('Identificador externo do pedido.');
            $table->string('requestor_name')->comment('Nome do solicitante da viagem.');
            $table->string('destination')->comment('Destino da viagem.');
            $table->dateTime('departure_date')->comment('Data de partida da viagem.');
            $table->dateTime('return_date')->comment('Data de retorno da viagem.');
            $table->enum('status', ['requested', 'approved', 'canceled'])->default('requested')->comment('Status do pedido de viagem.');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('travel_requests');
    }
};
