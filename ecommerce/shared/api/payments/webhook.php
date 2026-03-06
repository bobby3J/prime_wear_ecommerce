<?php
require_once __DIR__ . '/../bootstrap.php';

use Infrastructure\Http\GatewayWebhookController;

// POST /payments/webhook.php?provider=mtn_momo|telecel_cash|bank
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    api_error('Method not allowed.', 405);
}

$provider = (string) ($_GET['provider'] ?? '');
$rawBody = (string) file_get_contents('php://input');

/**
 * Normalizes request headers for Apache/FPM/CLI variants.
 */
function webhook_headers(): array
{
    if (function_exists('getallheaders')) {
        $headers = getallheaders();
        if (is_array($headers)) {
            return $headers;
        }
    }

    $headers = [];
    foreach ($_SERVER as $name => $value) {
        if (!str_starts_with((string) $name, 'HTTP_')) {
            continue;
        }
        $header = str_replace('_', '-', strtolower(substr((string) $name, 5)));
        $headers[$header] = (string) $value;
    }
    return $headers;
}

try {
    $controller = new GatewayWebhookController();
    $data = $controller->handle($provider, webhook_headers(), $rawBody);
    api_success($data);
} catch (\Throwable $e) {
    api_error($e->getMessage(), 400);
}

