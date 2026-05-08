<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$sqlPath = __DIR__ . '/EMUUUUUUU.sql';

if (!file_exists($sqlPath)) {
    die("File not found: $sqlPath\n");
}

echo "Importing $sqlPath...\n";

// Read the file content
$sql = file_get_contents($sqlPath);

try {
    DB::unprepared($sql);
    echo "Import successful!\n";
} catch (\Exception $e) {
    echo "Error during import: " . $e->getMessage() . "\n";
}
