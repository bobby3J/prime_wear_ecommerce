<?php
namespace Infrastructure\Routes;

use Infrastructure\Http\PaymentController;

$controller = new PaymentController();

$router->get('/admin/payments/view', [$controller, 'view']);
