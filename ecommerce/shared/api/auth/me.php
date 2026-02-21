<?php
require_once __DIR__ . '/../bootstrap.php';

use Infrastructure\Http\AuthController;

// GET session identity endpoint used by frontend bootstrapping.
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    api_error('Method not allowed.', 405);
}

try {
    $controller = new AuthController();
    $customer = $controller->me();
    api_success([
        'authenticated' => $customer !== null,
        'customer' => $customer
    ]);
} catch (\Throwable $e) {
    api_error($e->getMessage(), 400);
}
