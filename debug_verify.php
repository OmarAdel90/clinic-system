<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Modules\Warehouse\Models\WarehouseInventory;
use Modules\Supplier\Models\SupplierPaymentHistory;
use Modules\Transaction\Models\WarehouseSupplierTransaction;

$txn = WarehouseSupplierTransaction::orderBy('created_at', 'desc')->first();
echo "Transaction ID: " . $txn->transaction_id . PHP_EOL;
echo "Items: " . json_encode($txn->items_bought) . PHP_EOL;

$inventory = WarehouseInventory::where('sku', 'SKU-TEST-123')->first();
echo "Inventory Quantity: " . ($inventory ? $inventory->quantity : 'NOT FOUND') . PHP_EOL;

$payment = SupplierPaymentHistory::where('transaction_id', $txn->transaction_id)->first();
echo "Payment History - Total: " . ($payment ? $payment->total_amount : 'NOT FOUND') . PHP_EOL;
echo "Payment History - Status: " . ($payment ? $payment->payment_status : 'NOT FOUND') . PHP_EOL;
