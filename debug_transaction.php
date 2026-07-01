<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Modules\Transaction\Models\WarehouseSupplierTransaction;
use Modules\Transaction\Services\TransactionService;
use Modules\Supplier\Models\Supplier;
use Modules\Warehouse\Models\Warehouse;
use Modules\Clinic\Models\Clinic;

$clinic = Clinic::first();
if (!$clinic) {
    $clinic = Clinic::create([
        'name' => 'Test Clinic',
        'phone_number' => '12345678',
        'address' => 'Test Address',
        'provides_medication' => true,
        'departments' => ['General'],
        'doctors' => [],
        'services' => [],
    ]);
}

$warehouse = Warehouse::firstOrCreate(
    ['name' => 'Test Warehouse'],
    ['clinic_id' => $clinic->id]
);

$supplier = Supplier::firstOrCreate(
    ['name' => 'Test Supplier'],
    ['phone_number' => '0987654321']
);

echo "Supplier ID: " . $supplier->id . PHP_EOL;
echo "Warehouse ID: " . $warehouse->id . PHP_EOL;

$data = [
    'warehouse_id' => $warehouse->id,
    'supplier_id' => $supplier->id,
    'batch_number' => 'BATCH-' . strtoupper(uniqid()),
    'transaction_date' => now()->toDateTimeString(),
    'items_bought' => [
        [
            'sku' => 'SKU-TEST-123',
            'name' => 'Test Product',
            'quantity' => 10,
            'price' => 50.00
        ]
    ]
];

try {
    $service = app(TransactionService::class);
    $transaction = $service->create($data);
    echo "Transaction created successfully! ID: " . $transaction->transaction_id . PHP_EOL;
} catch (\Throwable $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
    echo $e->getTraceAsString() . PHP_EOL;
}
