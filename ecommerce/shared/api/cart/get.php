<?php
require_once __DIR__ . '/../bootstrap.php';

use Infrastructure\Auth\SessionAuth;
use Infrastructure\Http\CartController;

// GET full cart snapshot for current authenticated customer.
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    api_error('Method not allowed.', 405);
}

SessionAuth::requireCustomer();
$customerId = SessionAuth::customerId();

try {
    $controller = new CartController();
    $cart = $controller->get((int) $customerId);
    api_success($cart);
} catch (\Throwable $e) {
    api_error($e->getMessage(), 400);
}
