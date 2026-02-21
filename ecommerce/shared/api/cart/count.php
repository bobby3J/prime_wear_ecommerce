<?php
require_once __DIR__ . '/../bootstrap.php';

use Infrastructure\Auth\SessionAuth;
use Infrastructure\Http\CartController;

// GET cart count for badge rendering.
// Returns zero for non-authenticated users.
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    api_error('Method not allowed.', 405);
}

if (!SessionAuth::isCustomerAuthenticated()) {
    api_success(['count' => 0]);
}

try {
    $controller = new CartController();
    $count = $controller->count((int) SessionAuth::customerId());
    api_success(['count' => $count]);
} catch (\Throwable $e) {
    api_error($e->getMessage(), 400);
}
