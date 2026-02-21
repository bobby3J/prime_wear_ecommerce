<?php
namespace Infrastructure\Routes;

use Infrastructure\Http\AdminCustomerController;

$controller = new AdminCustomerController();

$router->get('/admin/customers/view', [$controller, 'view']);
$router->get('/admin/customers/show', [$controller, 'show']);

