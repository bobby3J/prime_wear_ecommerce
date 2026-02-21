<?php
require_once __DIR__ . '/../bootstrap.php';

use Infrastructure\Auth\SessionAuth;
use Infrastructure\Http\CheckoutController;

// POST /checkout/confirm
// Validates name/phone/street_address and stores a checkout confirmation checkpoint.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    api_error('Method not allowed.', 405);
}

SessionAuth::requireCustomer();
$customerId = (int) SessionAuth::customerId();

try {
    $controller = new CheckoutController();
    $data = $controller->confirm($customerId, api_input());
    api_success($data);
} catch (\Throwable $e) {
    api_error($e->getMessage(), 400);
}
