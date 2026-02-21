<?php
require_once __DIR__ . '/../bootstrap.php';

use Infrastructure\Http\AuthController;

// POST customer registration endpoint.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    api_error('Method not allowed.', 405);
}

try {
    $controller = new AuthController();
    $customer = $controller->register(api_input());
    api_success(['customer' => $customer], 201);
} catch (\Throwable $e) {
    api_error($e->getMessage(), 400);
}
