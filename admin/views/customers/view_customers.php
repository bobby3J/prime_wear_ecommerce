<?php
$filters = $filters ?? ['q' => '', 'status' => ''];
$pagination = $pagination ?? ['page' => 1, 'per_page' => 10, 'total' => 0, 'total_pages' => 1];
$customers = $customers ?? [];

$page = (int) ($pagination['page'] ?? 1);
$perPage = (int) ($pagination['per_page'] ?? 10);
$total = (int) ($pagination['total'] ?? 0);
$totalPages = (int) ($pagination['total_pages'] ?? 1);

$queryBase = array_filter([
    'q' => $filters['q'] !== '' ? $filters['q'] : null,
    'status' => $filters['status'] !== '' ? $filters['status'] : null,
    'per_page' => $perPage !== 10 ? $perPage : null,
], static fn($v) => $v !== null && $v !== '');

$start = $total > 0 ? (($page - 1) * $perPage + 1) : 0;
$end = $total > 0 ? min($page * $perPage, $total) : 0;
?>

<div class="card border-0 shadow-sm">
  <div class="card-body">
    <h3 class="text-center mb-4 text-info fw-bold">Customers</h3>

    <form class="row g-3 mb-3" method="get" action="/admin/customers/view">
      <div class="col-md-5">
        <input type="text" name="q" class="form-control" placeholder="Search by name or email" value="<?= htmlspecialchars($filters['q']) ?>">
      </div>
      <div class="col-md-3">
        <select name="status" class="form-select">
          <option value="">All status</option>
          <option value="active" <?= $filters['status'] === 'active' ? 'selected' : '' ?>>Active</option>
          <option value="inactive" <?= $filters['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
        </select>
      </div>
      <div class="col-md-2">
        <select name="per_page" class="form-select">
          <option value="10" <?= $perPage === 10 ? 'selected' : '' ?>>10 / page</option>
          <option value="25" <?= $perPage === 25 ? 'selected' : '' ?>>25 / page</option>
          <option value="50" <?= $perPage === 50 ? 'selected' : '' ?>>50 / page</option>
        </select>
      </div>
      <div class="col-md-2 d-flex gap-2">
        <button type="submit" class="btn btn-outline-primary w-100">Apply</button>
        <a href="/admin/customers/view" class="btn btn-outline-secondary w-100">Reset</a>
      </div>
    </form>

    <div class="text-muted mb-2">Showing <?= $start ?>-<?= $end ?> of <?= $total ?> customers</div>

    <table class="table table-bordered table-striped align-middle text-center">
      <thead class="table-info text-dark">
        <tr>
          <th>#</th>
          <th>Name</th>
          <th>Email</th>
          <th>Status</th>
          <th>Carts</th>
          <th>Orders</th>
          <th>Payments</th>
          <th>Joined</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
      <?php if (empty($customers)): ?>
        <tr><td colspan="9" class="text-muted">No customers found.</td></tr>
      <?php else: ?>
        <?php foreach ($customers as $customer): ?>
          <?php $badge = ($customer['status'] ?? 'inactive') === 'active' ? 'bg-success' : 'bg-danger'; ?>
          <tr>
            <td><?= (int) $customer['id'] ?></td>
            <td><?= htmlspecialchars($customer['name']) ?></td>
            <td><?= htmlspecialchars($customer['email']) ?></td>
            <td><span class="badge <?= $badge ?>"><?= htmlspecialchars(ucfirst($customer['status'])) ?></span></td>
            <td><?= (int) $customer['carts_count'] ?></td>
            <td><?= (int) $customer['orders_count'] ?></td>
            <td><?= (int) $customer['payments_count'] ?></td>
            <td><?= htmlspecialchars(date('Y-m-d', strtotime($customer['created_at']))) ?></td>
            <td>
              <a href="/admin/customers/show?id=<?= (int) $customer['id'] ?>" class="btn btn-sm btn-outline-info">
                <i class="fa fa-eye"></i>
              </a>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
      </tbody>
    </table>

    <?php if ($totalPages > 1): ?>
      <nav aria-label="Customers pagination">
        <ul class="pagination justify-content-center mb-0">
          <?php $prev = $queryBase; $prev['page'] = max(1, $page - 1); ?>
          <?php $next = $queryBase; $next['page'] = min($totalPages, $page + 1); ?>
          <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
            <a class="page-link" href="/admin/customers/view?<?= http_build_query($prev) ?>">Previous</a>
          </li>
          <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
            <?php $qp = $queryBase; $qp['page'] = $i; ?>
            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
              <a class="page-link" href="/admin/customers/view?<?= http_build_query($qp) ?>"><?= $i ?></a>
            </li>
          <?php endfor; ?>
          <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
            <a class="page-link" href="/admin/customers/view?<?= http_build_query($next) ?>">Next</a>
          </li>
        </ul>
      </nav>
    <?php endif; ?>
  </div>
</div>

