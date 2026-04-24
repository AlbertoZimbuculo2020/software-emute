<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());
use Illuminate\Support\Facades\DB;

echo "--- tb_sub_categoria ---\n";
print_r(DB::table('tb_sub_categoria')->get()->toArray());

echo "\n--- tb_imposto ---\n";
print_r(DB::table('tb_imposto')->get()->toArray());

echo "\n--- tb_motivo_isencao ---\n";
print_r(DB::table('tb_motivo_isencao')->get()->toArray());
