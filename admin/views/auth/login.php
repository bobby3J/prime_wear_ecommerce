<?php
$error = trim((string) ($error ?? ''));
$notice = trim((string) ($notice ?? ''));
$canBootstrap = (bool) ($canBootstrap ?? false);
?>

<div class="row g-4 justify-content-center">
  <div class="col-12 col-lg-6">
    <div class="card border-0 shadow-sm">
      <div class="card-body p-4 p-md-5">
        <h3 class="mb-2">Admin Login</h3>
        <p class="text-muted mb-4">Sign in as an admin or superadmin.</p>

        <?php if ($error !== ''): ?>
          <div class="alert alert-danger py-2"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($notice !== ''): ?>
          <div class="alert alert-success py-2"><?= htmlspecialchars($notice) ?></div>
        <?php endif; ?>

        <form method="post" action="/admin/login" novalidate>
          <div class="mb-3">
            <label class="form-label" for="adminEmail">Email</label>
            <input type="email" class="form-control" id="adminEmail" name="email" required>
          </div>
          <div class="mb-3">
            <label class="form-label" for="adminPassword">Password</label>
            <input type="password" class="form-control" id="adminPassword" name="password" required>
          </div>
          <button type="submit" class="btn btn-info text-white w-100">Login</button>
        </form>
      </div>
    </div>
  </div>

  <?php if ($canBootstrap): ?>
    <div class="col-12 col-lg-6">
      <div class="card border-0 shadow-sm">
        <div class="card-body p-4 p-md-5">
          <h4 class="mb-2">Initial Setup</h4>
          <p class="text-muted mb-4">No users found. Create the first superadmin account.</p>

          <form method="post" action="/admin/bootstrap-superadmin" novalidate>
            <div class="mb-3">
              <label class="form-label" for="bootstrapName">Full Name</label>
              <input type="text" class="form-control" id="bootstrapName" name="name" required>
            </div>
            <div class="mb-3">
              <label class="form-label" for="bootstrapEmail">Email</label>
              <input type="email" class="form-control" id="bootstrapEmail" name="email" required>
            </div>
            <div class="mb-3">
              <label class="form-label" for="bootstrapPassword">Password</label>
              <input type="password" class="form-control" id="bootstrapPassword" name="password" minlength="8" required>
            </div>
            <button type="submit" class="btn btn-success w-100">Create First Superadmin</button>
          </form>
        </div>
      </div>
    </div>
  <?php endif; ?>
</div>