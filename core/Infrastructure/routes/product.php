<?php
namespace Infrastructure\Routes;

use Infrastructure\Http\ProductController;

$controller = new ProductController();

$router->get('/admin/products/view', [$controller, 'view']);

$router->get('/admin/products/create', [$controller, 'create']);

$router->post('/admin/products/create', [$controller, 'store']);

$router->get('/admin/products/edit', [$controller, 'edit']);
$router->post('/admin/products/edit', [$controller, 'update']);

$router->get('/admin/products/delete', [$controller, 'delete']);


// old school method
// if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_SERVER['REQUEST_URI'] === '/admin/products/create') {
//     $controller->store();
// }
