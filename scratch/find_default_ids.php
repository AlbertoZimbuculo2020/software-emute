<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());
use Illuminate\Support\Facades\DB;

echo "--- tb_marca ---\n";
print_r(DB::table('tb_marca')->first());

echo "\n--- tb_unidade ---\n";
print_r(DB::table('tb_unidade')->first());

echo "\n--- tb_sub_categoria ---\n";
print_r(DB::table('tb_sub_categoria')->first());
