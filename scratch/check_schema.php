<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$tables = ['tb_prescricao'];

foreach ($tables as $table) {
    if (Schema::hasTable($table)) {
        echo "\nTable: $table\n";
        $columns = DB::select("DESCRIBE $table");
        foreach ($columns as $column) {
            echo " - {$column->Field}: {$column->Type}\n";
        }
    } else {
        echo "\nTable: $table DOES NOT EXIST\n";
    }
}
