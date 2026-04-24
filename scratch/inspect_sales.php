<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());
use Illuminate\Support\Facades\DB;

echo "--- tb_venda ---\n";
foreach(DB::select('DESCRIBE tb_venda') as $f) echo $f->Field . " (" . $f->Type . ")\n";

// Procurar por itens de venda
$tables = DB::select('SHOW TABLES');
foreach($tables as $table) {
    $name = current((array)$table);
    if(str_contains($name, 'venda') && $name != 'tb_venda') {
        echo "\n--- $name ---\n";
        foreach(DB::select("DESCRIBE $name") as $f) echo $f->Field . " (" . $f->Type . ")\n";
    }
}
