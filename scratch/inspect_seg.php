<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());
use Illuminate\Support\Facades\DB;
echo "--- tb_seguradora ---\n";
foreach(DB::select('DESCRIBE tb_seguradora') as $f) echo $f->Field . "\n";
echo "--- tb_tipoentidade ---\n";
foreach(DB::select('DESCRIBE tb_tipoentidade') as $f) echo $f->Field . "\n";
