<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());
use Illuminate\Support\Facades\DB;
$cols = DB::select('DESCRIBE tb_exames');
foreach($cols as $c) {
    echo $c->Field . " (" . $c->Type . ")\n";
}
