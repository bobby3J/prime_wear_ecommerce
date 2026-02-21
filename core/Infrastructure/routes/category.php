<?php
namespace Infrastructure\Routes;

use Infrastructure\Http\CategoryController;

$controller = new CategoryController();

$router->get('/admin/categories/view', [$controller, 'view']);
$router->get('/admin/categories/create', [$controller, 'create']);
$router->post('/admin/categories/create', [$controller, 'store']);
$router->get('/admin/categories/edit', [$controller, 'edit']);
$router->post('/admin/categories/edit', [$controller, 'update']);
$router->get('/admin/categories/delete', [$controller, 'delete']);
