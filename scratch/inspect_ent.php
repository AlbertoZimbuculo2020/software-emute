<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());
use Illuminate\Support\Facades\DB;
echo "--- tb_entidade ---\n";
foreach(DB::select('DESCRIBE tb_entidade') as $f) echo $f->Field . "\n";
