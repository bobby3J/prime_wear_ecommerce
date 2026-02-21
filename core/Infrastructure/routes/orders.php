<?php
namespace Infrastructure\Routes;

use Infrastructure\Http\OrderController;

$controller = new OrderController();

$router->get('/admin/orders/view', [$controller, 'view']);
$router->get('/admin/orders/show', [$controller, 'show']);
