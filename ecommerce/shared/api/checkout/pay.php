<?php
require_once __DIR__ . '/../bootstrap.php';

use Infrastructure\Auth\SessionAuth;
use Infrastructure\Http\CheckoutController;

// POST /checkout/pay
// Executes payment flow after confirmation:
// - creates order
// - creates order_items
// - creates order_delivery_details
// - creates payment
// - provider methods are initiated as pending and finalized by webhook
// - COD remains pending until delivery collection
// Request fields:
// - method: mtn_momo | telecel_cash | bank | cash_on_delivery
// - transaction_ref: optional merchant reference
// - payer_phone: required for mobile money prompt flow
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    api_error('Method not allowed.', 405);
}

SessionAuth::requireCustomer();
$customerId = (int) SessionAuth::customerId();

try {
    $controller = new CheckoutController();
    $data = $controller->pay($customerId, api_input());
    api_success($data);
} catch (\Throwable $e) {
    api_error($e->getMessage(), 400);
}
