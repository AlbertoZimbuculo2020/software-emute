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
        Schema::table('tb_triagem', function (Blueprint $table) {
            $table->string('PressaoArterialBE', 50)->nullable();
            $table->string('PulsoBE', 50)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tb_triagem', function (Blueprint $table) {
            $table->dropColumn(['PressaoArterialBE', 'PulsoBE']);
        });
    }
};
