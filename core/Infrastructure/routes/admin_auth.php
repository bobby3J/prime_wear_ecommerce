<?php
namespace Infrastructure\Routes;

use Infrastructure\Http\AdminAuthController;

$controller = new AdminAuthController();

// Public/admin-auth routes for admin area entry.
$router->get('/admin/login', [$controller, 'showLogin']);
$router->post('/admin/login', [$controller, 'login']);
$router->post('/admin/logout', [$controller, 'logout']);
$router->post('/admin/bootstrap-superadmin', [$controller, 'bootstrapFirstSuperadmin']);
