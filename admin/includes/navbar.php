<?php
$adminName = \Infrastructure\Auth\SessionAuth::adminName() ?? 'Admin';
$adminRole = \Infrastructure\Auth\SessionAuth::adminRole() ?? 'admin';
?>
<nav class="navbar navbar-expand-lg bg-info shadow-sm fixed-top">
  <div class="container-fluid">
    <a href="/admin/dashboard" class="navbar-brand fw-bold text-white d-flex align-items-center">
      <i class="fa-solid fa-screwdriver-wrench me-2"></i> Admin Dashboard
    </a>
    <div class="ms-auto d-flex align-items-center gap-2">
      <span class="text-white me-2">
        Welcome, <?= htmlspecialchars($adminName) ?>
        <small class="text-white-50">(<?= htmlspecialchars(ucfirst($adminRole)) ?>)</small>
      </span>
      <form method="post" action="/admin/logout" class="m-0">
        <button class="btn btn-outline-light btn-sm" type="submit">Logout</button>
      </form>
    </div>
  </div>
</nav>