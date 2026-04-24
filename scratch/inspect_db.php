<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

use Illuminate\Support\Facades\DB;

$tables = DB::select('SHOW TABLES');
foreach ($tables as $table) {
    $tableName = current((array)$table);
    if (str_contains($tableName, 'consulta')) {
        echo $tableName . PHP_EOL;
    }
}
