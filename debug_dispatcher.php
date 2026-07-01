<?php
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Get first user with admin role
$user = \Modules\Auth\Models\User::whereHas('roles', fn($q) => $q->where('name', 'admin'))->first();
if (!$user) {
    $user = \Modules\Auth\Models\User::first();
}

echo "User: " . ($user->id ?? 'none') . " - " . ($user->name ?? 'none') . PHP_EOL;
echo "Has admin role: " . ($user->hasRole('admin') ? 'yes' : 'no') . PHP_EOL;
echo "Has view_clinic: " . ($user->can('view_clinic') ? 'yes' : 'no') . PHP_EOL;

try {
    // Simulate the request security check
    $request = \Illuminate\Http\Request::create('/api/clinics/2', 'GET', [], [], [], [
        'HTTP_ACCEPT' => 'application/json',
        'HTTP_AUTHORIZATION' => 'Bearer test',
    ]);
    
    // Bind user to request
    $request->setUserResolver(function () use ($user) {
        return $user;
    });
    
    $showRequest = new \Modules\Clinic\Requests\ShowClinicRequest(query: [], request: $request->all(), attributes: $request->attributes, cookies: $request->cookies, files: $request->files, server: $request->server, content: $request->getContent());
    $ref = new ReflectionClass($showRequest);
    $converter = $ref->getMethod('setContainer');
    $converter->setAccessible(true);
    $converter->invoke($showRequest, $app);
    
    $result = $showRequest->authorize();
    echo "ShowClinicRequest authorize: " . ($result ? 'PASS' : 'FAIL') . PHP_EOL;
} catch (\Throwable $e) {
    echo "ShowClinicRequest error: " . get_class($e) . " - " . $e->getMessage() . PHP_EOL;
}

// Try route model binding directly
try {
    $clinic = \Modules\Clinic\Models\Clinic::findOrFail(2);
    echo "Route binding: Clinic 2 found - " . $clinic->name . PHP_EOL;
} catch (\Throwable $e) {
    echo "Route binding error: " . $e->getMessage() . PHP_EOL;
}
