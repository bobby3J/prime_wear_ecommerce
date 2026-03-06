<?php
require_once __DIR__ . '/bootstrap.php';

use Core\Infrastructure\Persistence\Database;
use Infrastructure\Query\StorefrontProductQueryService;

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    api_error('Method not allowed.', 405);
}

// GET /ecommerce/shared/api/products.php
// Thin API handler:
// 1) Read request query params.
// 2) Pass filters to StorefrontProductQueryService.
// 3) Return normalized product list in shared API JSON format.
try {
    $pdo = Database::getConnection();

    // Query service executes the SQL and shapes rows for frontend consumers.
    $queryService = new StorefrontProductQueryService($pdo);
    $products = $queryService->fetchActiveProducts([
        // q/category/category_id are optional.
        // The service handles precedence and empty/default values.
        'q' => $_GET['q'] ?? '',
        'category' => $_GET['category'] ?? '',
        'category_id' => $_GET['category_id'] ?? 0,
    ]);

    api_success([
        'count' => count($products),
        'items' => $products
    ]);
} catch (\Throwable $e) {
    api_error($e->getMessage(), 500);
}
