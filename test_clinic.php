<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$user = \Modules\Auth\Models\User::find(1);
$token = $user->createToken('test')->plainTextToken;

$url = 'http://127.0.0.1:8889/api/clinics/2';

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json',
    'Authorization: Bearer ' . $token,
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo 'HTTP Status: ' . $httpCode . PHP_EOL;
echo 'Response: ' . $response . PHP_EOL;
