<?php
require_once __DIR__ . '/../bootstrap.php';

use Infrastructure\Http\AuthController;

// POST logout endpoint. Clears customer session keys.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    api_error('Method not allowed.', 405);
}

try {
    $controller = new AuthController();
    $controller->logout();
    api_success(['message' => 'Logged out.']);
} catch (\Throwable $e) {
    api_error($e->getMessage(), 400);
}
