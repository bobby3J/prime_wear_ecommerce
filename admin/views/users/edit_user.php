<?php
$flashError = trim((string) ($flashError ?? ''));
$user = $user ?? null;

if (!is_array($user)) {
  echo '<div class="alert alert-danger">User data is unavailable.</div>';
  return;
}
?>

<div class="card border-0 shadow-sm">
  <div class="card-body">
    <h4 class="mb-3">Edit Admin User</h4>

    <?php if ($flashError !== ''): ?>
      <div class="alert alert-danger py-2"><?= htmlspecialchars($flashError) ?></div>
    <?php endif; ?>

    <form method="post" action="/admin/users/edit" class="row g-3">
      <input type="hidden" name="id" value="<?= (int) ($user['id'] ?? 0) ?>">

      <div class="col-md-6">
        <label class="form-label" for="userName">Full Name</label>
        <input
          type="text"
          class="form-control"
          id="userName"
          name="name"
          value="<?= htmlspecialchars((string) ($user['name'] ?? '')) ?>"
          required
        >
      </div>

      <div class="col-md-6">
        <label class="form-label" for="userEmail">Email</label>
        <input
          type="email"
          class="form-control"
          id="userEmail"
          name="email"
          value="<?= htmlspecialchars((string) ($user['email'] ?? '')) ?>"
          required
        >
      </div>

      <div class="col-md-6">
        <label class="form-label" for="userPassword">New Password (optional)</label>
        <input type="password" class="form-control" id="userPassword" name="password" minlength="8" placeholder="Leave blank to keep current password">
      </div>

      <div class="col-md-3">
        <label class="form-label" for="userRole">Role</label>
        <select class="form-select" id="userRole" name="role" required>
          <option value="admin" <?= (($user['role'] ?? '') === 'admin') ? 'selected' : '' ?>>Admin</option>
          <option value="superadmin" <?= (($user['role'] ?? '') === 'superadmin') ? 'selected' : '' ?>>Superadmin</option>
        </select>
      </div>

      <div class="col-md-3">
        <label class="form-label" for="userStatus">Status</label>
        <select class="form-select" id="userStatus" name="status" required>
          <option value="active" <?= (($user['status'] ?? '') === 'active') ? 'selected' : '' ?>>Active</option>
          <option value="inactive" <?= (($user['status'] ?? '') === 'inactive') ? 'selected' : '' ?>>Inactive</option>
        </select>
      </div>

      <div class="col-12 d-flex gap-2">
        <button type="submit" class="btn btn-info text-white">Save Changes</button>
        <a href="/admin/users/view" class="btn btn-outline-secondary">Cancel</a>
      </div>
    </form>
  </div>
</div>