<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE tb_tipoentidade ADD INDEX idx_tb_tipoentidade_codigo (Codigo(191))');

        Schema::table('tb_resultado_exame', function (Blueprint $table) {
            $table->index('Estado', 'idx_resultado_estado');
            $table->index('IdAgenda', 'idx_resultado_idagenda');
        });

        Schema::table('tb_prescricao', function (Blueprint $table) {
            $table->index('Estado', 'idx_prescricao_estado');
            $table->index('Tipo', 'idx_prescricao_tipo');
            $table->index('Cumprimento', 'idx_prescricao_cumprimento');
        });
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE tb_tipoentidade DROP INDEX idx_tb_tipoentidade_codigo');
        Schema::table('tb_resultado_exame', function (Blueprint $table) {
            $table->dropIndex('idx_resultado_estado');
            $table->dropIndex('idx_resultado_idagenda');
        });
        Schema::table('tb_prescricao', function (Blueprint $table) {
            $table->dropIndex('idx_prescricao_estado');
            $table->dropIndex('idx_prescricao_tipo');
            $table->dropIndex('idx_prescricao_cumprimento');
        });
    }
};
