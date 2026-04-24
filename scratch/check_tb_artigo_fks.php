<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());
use Illuminate\Support\Facades\DB;

$fks = DB::select("
    SELECT COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME 
    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
    WHERE TABLE_NAME = 'tb_artigo' 
    AND TABLE_SCHEMA = 'emute'
    AND REFERENCED_TABLE_NAME IS NOT NULL
");

foreach($fks as $fk) {
    echo "Column: {$fk->COLUMN_NAME} -> {$fk->REFERENCED_TABLE_NAME}.{$fk->REFERENCED_COLUMN_NAME}\n";
    $first = DB::table($fk->REFERENCED_TABLE_NAME)->first();
    if($first) {
        $refCol = $fk->REFERENCED_COLUMN_NAME;
        echo "   Example valid value: " . $first->$refCol . "\n";
    } else {
        echo "   WARNING: Referenced table is empty!\n";
    }
}
