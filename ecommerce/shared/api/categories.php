<?php
require_once __DIR__ . '/bootstrap.php';

use Core\Infrastructure\Persistence\Database;
use Infrastructure\Query\StorefrontCategoryQueryService;

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    api_error('Method not allowed.', 405);
}

// GET /ecommerce/shared/api/categories.php
// Thin API handler:
// 1) Validate HTTP method.
// 2) Delegate category read logic to StorefrontCategoryQueryService.
// 3) Return standardized JSON payload via api_success().
try {
    $pdo = Database::getConnection();

    // Query service owns SQL + result normalization.
    // This file stays focused on HTTP concerns only.
    $queryService = new StorefrontCategoryQueryService($pdo);
    $items = $queryService->fetchActiveCategories();

    api_success([
        'count' => count($items),
        'items' => $items,
    ]);
} catch (\Throwable $e) {
    api_error($e->getMessage(), 500);
}
