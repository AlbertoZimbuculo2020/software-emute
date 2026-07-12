<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);
$body = $response->getContent();
if (preg_match('/data-page="([^"]*)"/', $body, $m)) {
    $json = html_entity_decode($m[1]);
    echo "Raw JSON (first 500): " . substr($json, 0, 500) . "\n";
    $data = json_decode($json, true);
    echo "JSON valid: " . (json_last_error() === JSON_ERROR_NONE ? 'YES' : 'NO: ' . json_last_error_msg()) . "\n";
    echo "Component: " . var_export($data['component'] ?? 'MISSING', true) . "\n";
} else {
    echo "No data-page found\n";
    echo "First 500: " . substr($body, 0, 500) . "\n";
}
echo "\nStatus: " . $response->getStatusCode() . "\n";
