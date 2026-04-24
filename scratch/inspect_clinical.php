<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());
use Illuminate\Support\Facades\DB;

foreach(['tb_prescricao', 'tb_receita', 'tb_ficha_medica'] as $t) {
    echo "--- $t ---\n";
    foreach(DB::select("DESCRIBE $t") as $f) echo $f->Field . " (" . $f->Type . ")\n";
}
