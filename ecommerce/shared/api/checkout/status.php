<?php
require_once __DIR__ . '/../bootstrap.php';

use Infrastructure\Auth\SessionAuth;
use Infrastructure\Http\CheckoutController;

// GET /checkout/status
// Returns checkout confirmation state for current customer session.
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    api_error('Method not allowed.', 405);
}

SessionAuth::requireCustomer();
$customerId = (int) SessionAuth::customerId();

try {
    $controller = new CheckoutController();
    $data = $controller->status($customerId);
    api_success($data);
} catch (\Throwable $e) {
    api_error($e->getMessage(), 400);
}
