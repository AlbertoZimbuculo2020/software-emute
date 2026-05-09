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
        Schema::table('tb_agendamento', function (Blueprint $table) {
            $table->integer('ID_QUARTO')->nullable();
            $table->integer('ID_TIPOCAMA')->nullable();
            $table->string('TipoInternamento')->nullable();
            $table->text('DiagnosticoAdmissao')->nullable();
            $table->string('ICD10', 20)->nullable();
            $table->enum('Consentimento', ['SIM', 'NAO'])->default('NAO');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tb_agendamento', function (Blueprint $table) {
            //
        });
    }
};
