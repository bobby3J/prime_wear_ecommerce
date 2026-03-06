<?php
$appConfig = require __DIR__ . '/../config/app.php';
\Infrastructure\Auth\SessionAuth::start();

// Admin front controller:
// 1) Classify current request path.
// 2) Apply auth guard + role gate.
// 3) Register all route maps.
// 4) Dispatch and choose layout.
$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/admin', PHP_URL_PATH) ?: '/admin';
$publicPaths = [
    '/admin/login',
    '/admin/bootstrap-superadmin',
];

$isAuthenticatedAdmin = \Infrastructure\Auth\SessionAuth::isAdminAuthorized();
$isPublicPath = in_array($requestPath, $publicPaths, true);

if ($isAuthenticatedAdmin && $requestPath === '/admin/login') {
    header('Location: /admin/dashboard');
    exit;
}

if (!$isPublicPath && !$isAuthenticatedAdmin) {
    header('Location: /admin/login');
    exit;
}

// Users management is intentionally superadmin-only.
if (str_starts_with($requestPath, '/admin/users') && !\Infrastructure\Auth\SessionAuth::isSuperadmin()) {
    header('Location: /admin/dashboard?forbidden=1');
    exit;
}

use Infrastructure\Routes\Router;

$router = new Router();

/*
|--------------------------------------------------------------------------
| Register routes
|--------------------------------------------------------------------------
*/
require __DIR__ . '/../core/Infrastructure/routes/admin_auth.php';
require __DIR__ . '/../core/Infrastructure/routes/dashboard.php';
require __DIR__ . '/../core/Infrastructure/routes/product.php';
require __DIR__ . '/../core/Infrastructure/routes/category.php';
require __DIR__ . '/../core/Infrastructure/routes/customers.php';
require __DIR__ . '/../core/Infrastructure/routes/carts.php';
require __DIR__ . '/../core/Infrastructure/routes/orders.php';
require __DIR__ . '/../core/Infrastructure/routes/payments.php';
require __DIR__ . '/../core/Infrastructure/routes/users.php';

/*
|--------------------------------------------------------------------------
| Dispatch request
|--------------------------------------------------------------------------
*/
$result = $router->dispatch();

// Some handlers may do redirects and not return view arrays.
if (!is_array($result)) {
    exit;
}

$view = $result['view'] ?? 'dashboard.php';
$data = $result['data'] ?? [];
$layout = $result['layout'] ?? 'main';

extract($data);

// Render page view first, then inject into selected layout.
ob_start();
include __DIR__ . '/views/' . $view;
$content = ob_get_clean();

if ($layout === 'auth') {
    include __DIR__ . '/layout/auth_layout.php';
    return;
}

include __DIR__ . '/layout/main_layout.php';
