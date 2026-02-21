<?php
namespace Infrastructure\Routes;

use Infrastructure\Http\DashboardController;

$controller = new DashboardController();

$router->get('/admin/dashboard', [$controller, 'view']);

// Friendly aliases so /admin and /admin/index.php land on dashboard KPIs.
$router->get('/admin', [$controller, 'view']);
$router->get('/admin/index.php', [$controller, 'view']);

