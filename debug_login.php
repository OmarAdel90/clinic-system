<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$router = $app->make('router');
echo "API middleware group: " . json_encode($router->getMiddlewareGroups()['api'] ?? []) . "\n\n";

$routes = $router->getRoutes();

// Find login route
foreach ($routes as $route) {
    if (str_contains($route->uri(), 'login') && $route->methods()[0] === 'POST') {
        echo "URI: /{$route->uri()}\n";
        echo "Methods: " . json_encode($route->methods()) . "\n";
        echo "Middleware: " . json_encode($route->gatherMiddleware()) . "\n";
        echo "Action: " . json_encode($route->getAction()) . "\n";
        echo "\n";
    }
}

// Test the login
$request = new Illuminate\Http\Request();
$request->setMethod('POST');
$request->headers->set('Content-Type', 'application/json');
$request->headers->set('Accept', 'application/json');
$request->initialize([], [], [], [], [], [], json_encode(['email' => 'admin@clinic.com', 'password' => 'password']));
$request->setRouteResolver(function () use ($router, $request) {
    return $router->getRoutes()->match($request);
});

try {
    $response = $router->dispatch($request);
    echo "Response status: " . $response->getStatusCode() . "\n";
    echo "Response body: " . $response->getContent() . "\n";
} catch (\Throwable $e) {
    echo "Exception: " . get_class($e) . ": " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}
