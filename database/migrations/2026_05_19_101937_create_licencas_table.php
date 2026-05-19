<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('licencas', function (Blueprint $table) {
            $table->id();
            $table->string('email');
            $table->string('empresa');
            $table->string('nif', 20);
            $table->string('plano'); // mensal, semestral, anual
            $table->string('codigo_ativacao', 10);
            $table->boolean('ativado')->default(false);
            $table->date('data_inicio')->nullable();
            $table->date('data_fim')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('licencas');
    }
};
