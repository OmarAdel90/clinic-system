<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

try {
    // Create a request to GET /api/clinics/2
    $request = Illuminate\Http\Request::create('/api/clinics/2', 'GET');
    $request->headers->set('Accept', 'application/json');

    // Get a valid user token for auth
    $user = \Modules\Auth\Models\User::first();
    if ($user) {
        $token = $user->createToken('debug')->plainTextToken;
        $request->headers->set('Authorization', 'Bearer ' . $token);
    }

    $response = $kernel->handle($request);

    echo 'Status: ' . $response->getStatusCode() . PHP_EOL;
    echo 'Content: ' . $response->getContent() . PHP_EOL;
} catch (\Throwable $e) {
    echo 'Exception: ' . get_class($e) . PHP_EOL;
    echo 'Message: ' . $e->getMessage() . PHP_EOL;
    echo 'File: ' . $e->getFile() . ':' . $e->getLine() . PHP_EOL;
}
