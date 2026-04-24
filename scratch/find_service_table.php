<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());
use Illuminate\Support\Facades\DB;

$tables = DB::select('SHOW TABLES');
foreach($tables as $table) {
    $name = current((array)$table);
    // Procurar por qualquer coisa que pareça serviço, produto, artigo, emolumento
    if(preg_match('/servico|produto|artigo|emolumento|item/i', $name)) {
        echo "$name\n";
    }
}
