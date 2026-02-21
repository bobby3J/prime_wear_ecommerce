<?php
require_once __DIR__ . '/../../../config/app.php';

use Infrastructure\Auth\SessionAuth;

header('Content-Type: application/json; charset=utf-8');

// Shared API bootstrap:
// - Loads autoloader/app bootstrap
// - Starts session for auth context
// - Provides standard JSON success/error helpers
SessionAuth::start();

function api_success(array $data = [], int $status = 200): void
{
    http_response_code($status);
    echo json_encode([
        'success' => true,
        'data' => $data
    ]);
    exit;
}

function api_error(string $message, int $status = 400): void
{
    http_response_code($status);
    echo json_encode([
        'success' => false,
        'message' => $message
    ]);
    exit;
}

function api_input(): array
{
    if (!empty($_POST)) {
        return $_POST;
    }

    $raw = file_get_contents('php://input');
    if (!$raw) {
        return [];
    }

    $json = json_decode($raw, true);
    return is_array($json) ? $json : [];
}
