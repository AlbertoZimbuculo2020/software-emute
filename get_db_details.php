<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$tables = ['tb_tipoentidade', 'tb_paciente', 'tb_medico', 'tb_agendamento', 'tb_consulta', 'tb_seguradora'];

foreach ($tables as $table) {
    echo "Table: $table\n";
    try {
        $cols = DB::select("DESCRIBE $table");
        foreach ($cols as $col) {
            echo "  {$col->Field} ({$col->Type})\n";
        }
    } catch (\Exception $e) {
        echo "  Error: " . $e->getMessage() . "\n";
    }
    echo "--------------------\n";
}
