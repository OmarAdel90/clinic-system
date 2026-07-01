<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo 'DB: ' . DB::connection()->getDatabaseName() . PHP_EOL;
$clinics = Modules\Clinic\Models\Clinic::all(['id','name'])->toArray();
echo 'Clinics count: ' . count($clinics) . PHP_EOL;
echo json_encode($clinics) . PHP_EOL;

$clinic2 = Modules\Clinic\Models\Clinic::find(2);
echo 'Clinic 2: ' . ($clinic2 ? 'found' : 'NOT FOUND') . PHP_EOL;
