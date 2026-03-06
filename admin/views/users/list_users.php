<?php
$users = $users ?? [];
$filters = $filters ?? ['q' => '', 'role' => '', 'status' => ''];
$pagination = $pagination ?? ['page' => 1, 'per_page' => 10, 'total' => 0, 'total_pages' => 1];
$flashError = trim((string) ($flashError ?? ''));
$flashNotice = trim((string) ($flashNotice ?? ''));

$page = (int) ($pagination['page'] ?? 1);
$totalPages = max(1, (int) ($pagination['total_pages'] ?? 1));
$perPage = (int) ($pagination['per_page'] ?? 10);

$baseParams = [
  'q' => (string) ($filters['q'] ?? ''),
  'role' => (string) ($filters['role'] ?? ''),
  'status' => (string) ($filters['status'] ?? ''),
  'per_page' => $perPage,
];
?>

<div class="card border-0 shadow-sm">
  <div class="card-body">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h4 class="mb-0">Admin Users</h4>
      <a href="/admin/users/create" class="btn btn-sm btn-success">
        <i class="fa fa-user-plus me-1"></i>Create User
      </a>
    </div>

    <?php if ($flashError !== ''): ?>
      <div class="alert alert-danger py-2"><?= htmlspecialchars($flashError) ?></div>
    <?php endif; ?>

    <?php if ($flashNotice !== ''): ?>
      <div class="alert alert-success py-2"><?= htmlspecialchars($flashNotice) ?></div>
    <?php endif; ?>

    <form class="row g-3 mb-3" method="get" action="/admin/users/view">
      <div class="col-md-4">
        <label class="form-label small text-muted">Search</label>
        <input
          type="text"
          name="q"
          class="form-control"
          value="<?= htmlspecialchars((string) ($filters['q'] ?? '')) ?>"
          placeholder="Name or email"
        >
      </div>
      <div class="col-md-3">
        <label class="form-label small text-muted">Role</label>
        <select name="role" class="form-select">
          <option value="">All Roles</option>
          <option value="admin" <?= (($filters['role'] ?? '') === 'admin') ? 'selected' : '' ?>>Admin</option>
          <option value="superadmin" <?= (($filters['role'] ?? '') === 'superadmin') ? 'selected' : '' ?>>Superadmin</option>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label small text-muted">Status</label>
        <select name="status" class="form-select">
          <option value="">All Status</option>
          <option value="active" <?= (($filters['status'] ?? '') === 'active') ? 'selected' : '' ?>>Active</option>
          <option value="inactive" <?= (($filters['status'] ?? '') === 'inactive') ? 'selected' : '' ?>>Inactive</option>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label small text-muted">Per Page</label>
        <select name="per_page" class="form-select">
          <option value="10" <?= $perPage === 10 ? 'selected' : '' ?>>10</option>
          <option value="25" <?= $perPage === 25 ? 'selected' : '' ?>>25</option>
          <option value="50" <?= $perPage === 50 ? 'selected' : '' ?>>50</option>
        </select>
      </div>
      <div class="col-md-1 d-grid align-items-end">
        <button type="submit" class="btn btn-outline-primary">Go</button>
      </div>
    </form>

    <div class="table-responsive">
      <table class="table table-striped align-middle">
        <thead class="table-light">
          <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Email</th>
            <th>Role</th>
            <th>Status</th>
            <th>Created</th>
            <th class="text-end">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($users)): ?>
            <tr>
              <td colspan="7" class="text-center text-muted py-4">No users found.</td>
            </tr>
          <?php else: ?>
            <?php foreach ($users as $user): ?>
              <?php
                $status = strtolower((string) ($user['status'] ?? 'inactive'));
                $statusBadge = $status === 'active' ? 'bg-success' : 'bg-secondary';
                $role = strtolower((string) ($user['role'] ?? 'admin'));
                $roleBadge = $role === 'superadmin' ? 'bg-dark' : 'bg-primary';
                $id = (int) ($user['id'] ?? 0);
              ?>
              <tr>
                <td><?= $id ?></td>
                <td><?= htmlspecialchars((string) ($user['name'] ?? '')) ?></td>
                <td><?= htmlspecialchars((string) ($user['email'] ?? '')) ?></td>
                <td><span class="badge <?= $roleBadge ?>"><?= htmlspecialchars(ucfirst($role)) ?></span></td>
                <td><span class="badge <?= $statusBadge ?>"><?= htmlspecialchars(ucfirst($status)) ?></span></td>
                <td><?= htmlspecialchars((string) ($user['created_at'] ?? '')) ?></td>
                <td class="text-end">
                  <a href="/admin/users/edit?id=<?= $id ?>" class="btn btn-sm btn-outline-info">
                    <i class="fa fa-edit"></i>
                  </a>
                  <a
                    href="/admin/users/delete?id=<?= $id ?>"
                    class="btn btn-sm btn-outline-danger"
                    onclick="return confirm('Delete this user account?');"
                  >
                    <i class="fa fa-trash"></i>
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <?php if ($totalPages > 1): ?>
      <nav aria-label="Users pagination">
        <ul class="pagination justify-content-center mb-0">
          <?php
            $prevPage = max(1, $page - 1);
            $prevParams = $baseParams;
            $prevParams['page'] = $prevPage;
          ?>
          <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
            <a class="page-link" href="/admin/users/view?<?= http_build_query($prevParams) ?>">Previous</a>
          </li>

          <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <?php $qp = $baseParams; $qp['page'] = $i; ?>
            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
              <a class="page-link" href="/admin/users/view?<?= http_build_query($qp) ?>"><?= $i ?></a>
            </li>
          <?php endfor; ?>

          <?php
            $nextPage = min($totalPages, $page + 1);
            $nextParams = $baseParams;
            $nextParams['page'] = $nextPage;
          ?>
          <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
            <a class="page-link" href="/admin/users/view?<?= http_build_query($nextParams) ?>">Next</a>
          </li>
        </ul>
      </nav>
    <?php endif; ?>
  </div>
</div>