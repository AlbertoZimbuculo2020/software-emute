<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());
use Illuminate\Support\Facades\DB;

$tables = DB::select('SHOW TABLES');
foreach($tables as $table) {
    $name = current((array)$table);
    if(str_contains($name, 'receita') || str_contains($name, 'exame') || str_contains($name, 'solicitacao')) {
        echo "--- $name ---\n";
        print_r(DB::select("DESCRIBE $name"));
    }
}
