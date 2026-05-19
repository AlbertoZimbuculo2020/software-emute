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
        Schema::create('tb_senha', function (Blueprint $table) {
            $table->id('Id');
            $table->string('Codigo', 20)->unique();
            $table->string('Tipo', 30); // 'Geral', 'Preferencial', 'Triagem', 'Exame'
            $table->string('Estado', 20)->default('Pendente'); // 'Pendente', 'Chamado', 'Atendido', 'Cancelado'
            $table->string('Guiche', 50)->nullable(); // Ex: 'Guiché 1', 'Triagem 2', 'Consultório 1'
            $table->date('DataCriacao');
            $table->dateTime('DataChamada')->nullable();
            $table->dateTime('DataUltimaChamada')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tb_senha');
    }
};
