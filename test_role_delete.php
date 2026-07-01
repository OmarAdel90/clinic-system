<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Spatie\Permission\Models\Role;

try {
    $role = Role::find(1);
    if (!$role) {
        echo "Role 1 not found\n";
        exit(1);
    }
    echo "Found: " . $role->name . "\n";
    $role->delete();
    echo "Deleted successfully\n";
    $role2 = Role::find(1);
    echo "After delete, find: " . ($role2 ? "exists" : "null") . "\n";
} catch (\Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Class: " . get_class($e) . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}
