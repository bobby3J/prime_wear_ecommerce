<?php
namespace Infrastructure\Routes;

use Infrastructure\Http\AdminCartController;

$controller = new AdminCartController();

$router->get('/admin/carts/view', [$controller, 'view']);
$router->get('/admin/carts/show', [$controller, 'show']);

