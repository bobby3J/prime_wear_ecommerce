<?php
$appConfig = require __DIR__ . '/../config/app.php';
\Infrastructure\Auth\SessionAuth::start();

// Guard hook for admin area.
// During development, access stays open to avoid blocking local iteration.
// In non-development env, only admin/superadmin sessions are allowed.
if (($appConfig['env'] ?? 'development') !== 'development' && !\Infrastructure\Auth\SessionAuth::isAdminAuthorized()) {
    header('Location: /ecommerce/index.php?page=home');
    exit;
}


use Infrastructure\Routes\Router;

$router = new Router();

/*
|--------------------------------------------------------------------------
| Register routes
|--------------------------------------------------------------------------
*/
require __DIR__ . '/../core/Infrastructure/Routes/dashboard.php';
require __DIR__ . '/../core/Infrastructure/Routes/product.php';
require __DIR__ . '/../core/Infrastructure/Routes/category.php';
require __DIR__ . '/../core/Infrastructure/Routes/customers.php';
require __DIR__ . '/../core/Infrastructure/Routes/carts.php';
require __DIR__ . '/../core/Infrastructure/Routes/orders.php';
require __DIR__ . '/../core/Infrastructure/Routes/payments.php';

/*
|--------------------------------------------------------------------------
| Dispatch request
|--------------------------------------------------------------------------
*/
$result = $router->dispatch();

$view = $result['view'] ?? 'dashboard.php';
$data = $result['data'] ?? [];

// Make data available to the view
extract($data);

ob_start();
include __DIR__ . '/views/' . $view;
$content = ob_get_clean();

include __DIR__ . '/layout/main_layout.php';


















// // Handle product creation POST directly (no router needed)
// if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_GET['page'] ?? '') === 'create_product') {
//     require_once __DIR__ . '/../core/Infrastructure/http/ProductController.php';
//     $controller = new \Infrastructure\Http\ProductController();
//     $controller->store();
//     exit; // store() should redirect or output response
// }

// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);
