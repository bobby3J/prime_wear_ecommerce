<?php
$adminName = \Infrastructure\Auth\SessionAuth::adminName() ?? 'Admin';
$isSuperadmin = \Infrastructure\Auth\SessionAuth::isSuperadmin();
?>
<div class="sidebar">
  <button
    id="sidebarToggle"
    class="sidebar-toggle-btn"
    type="button"
    title="Collapse sidebar"
    aria-label="Collapse sidebar"
    aria-expanded="true"
  >
    <i class="fa-solid fa-angles-left"></i>
  </button>
  <img src="/admin/assets/images/sneaker1.jpg" alt="Admin">
  <h5 class="sidebar-title"><?= htmlspecialchars($adminName) ?></h5>

  <a href="/admin/dashboard" class="btn" title="Dashboard">
    <i class="fa fa-tachometer-alt"></i>
    <span class="menu-label">Dashboard</span>
  </a>
  <a href="/admin/products/create" class="btn" title="Insert Products">
    <i class="fa fa-plus"></i>
    <span class="menu-label">Insert Products</span>
  </a>
  <a href="/admin/products/view" class="btn" title="View Products">
    <i class="fa fa-eye"></i>
    <span class="menu-label">View Products</span>
  </a>
  <a href="/admin/categories/create" class="btn" title="Insert Categories">
    <i class="fa fa-folder-plus"></i>
    <span class="menu-label">Insert Categories</span>
  </a>
  <a href="/admin/categories/view" class="btn" title="View Categories">
    <i class="fa fa-list"></i>
    <span class="menu-label">View Categories</span>
  </a>

  <?php if ($isSuperadmin): ?>
    <a href="/admin/users/view" class="btn" title="Users">
      <i class="fa fa-user-shield"></i>
      <span class="menu-label">Users</span>
    </a>
  <?php endif; ?>

  <a href="/admin/customers/view" class="btn" title="Customers">
    <i class="fa fa-users"></i>
    <span class="menu-label">Customers</span>
  </a>
  <a href="/admin/carts/view" class="btn" title="Carts">
    <i class="fa fa-shopping-cart"></i>
    <span class="menu-label">Carts</span>
  </a>
  <a href="/admin/orders/view" class="btn" title="All Orders">
    <i class="fa fa-shopping-bag"></i>
    <span class="menu-label">All Orders</span>
  </a>
  <a href="/admin/payments/view" class="btn" title="All Payments">
    <i class="fa fa-credit-card"></i>
    <span class="menu-label">All Payments</span>
  </a>
</div>