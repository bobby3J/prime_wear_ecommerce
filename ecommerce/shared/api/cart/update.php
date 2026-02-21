<?php
require_once __DIR__ . '/../bootstrap.php';

use Infrastructure\Auth\SessionAuth;
use Infrastructure\Http\CartController;

// POST update quantity for an existing cart item.
// Input: item_id, quantity.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    api_error('Method not allowed.', 405);
}

SessionAuth::requireCustomer();
$customerId = (int) SessionAuth::customerId();

try {
    $controller = new CartController();
    $controller->update($customerId, api_input());
    $cart = $controller->get($customerId);
    api_success($cart);
} catch (\Throwable $e) {
    api_error($e->getMessage(), 400);
}
