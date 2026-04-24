<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());
use Illuminate\Support\Facades\DB;
echo "--- tb_artigo ---\n";
foreach(DB::select('DESCRIBE tb_artigo') as $f) echo $f->Field . " (" . $f->Type . ")\n";
