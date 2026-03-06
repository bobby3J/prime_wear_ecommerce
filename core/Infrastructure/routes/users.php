<?php
namespace Infrastructure\Routes;

use Infrastructure\Http\AdminUserController;

$controller = new AdminUserController();

// Superadmin-only user management routes.
// Access is enforced in admin/index.php and in AdminUserController itself.
$router->get('/admin/users/view', [$controller, 'view']);
$router->get('/admin/users/create', [$controller, 'create']);
$router->post('/admin/users/create', [$controller, 'store']);
$router->get('/admin/users/edit', [$controller, 'edit']);
$router->post('/admin/users/edit', [$controller, 'update']);
$router->get('/admin/users/delete', [$controller, 'delete']);
