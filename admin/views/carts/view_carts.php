<?php
$filters = $filters ?? ['q' => '', 'status' => ''];
$summary = $summary ?? ['active' => 0, 'abandoned' => 0, 'converted' => 0];
$pagination = $pagination ?? ['page' => 1, 'per_page' => 10, 'total' => 0, 'total_pages' => 1];
$carts = $carts ?? [];

$page = (int) ($pagination['page'] ?? 1);
$perPage = (int) ($pagination['per_page'] ?? 10);
$total = (int) ($pagination['total'] ?? 0);
$totalPages = (int) ($pagination['total_pages'] ?? 1);
$start = $total > 0 ? (($page - 1) * $perPage + 1) : 0;
$end = $total > 0 ? min($page * $perPage, $total) : 0;

$queryBase = array_filter([
    'q' => $filters['q'] !== '' ? $filters['q'] : null,
    'status' => $filters['status'] !== '' ? $filters['status'] : null,
    'per_page' => $perPage !== 10 ? $perPage : null,
], static fn($v) => $v !== null && $v !== '');
?>

<div class="card border-0 shadow-sm">
  <div class="card-body">
    <h3 class="text-center mb-4 text-info fw-bold">Carts Monitoring</h3>

    <div class="row g-3 mb-4">
      <div class="col-md-4"><div class="card border-0 shadow-sm"><div class="card-body"><strong>Active</strong><div class="display-6"><?= (int) $summary['active'] ?></div></div></div></div>
      <div class="col-md-4"><div class="card border-0 shadow-sm"><div class="card-body"><strong>Abandoned</strong><div class="display-6"><?= (int) $summary['abandoned'] ?></div></div></div></div>
      <div class="col-md-4"><div class="card border-0 shadow-sm"><div class="card-body"><strong>Converted</strong><div class="display-6"><?= (int) $summary['converted'] ?></div></div></div></div>
    </div>

    <form class="row g-3 mb-3" method="get" action="/admin/carts/view">
      <div class="col-md-5">
        <input type="text" name="q" class="form-control" placeholder="Search by customer name or email" value="<?= htmlspecialchars($filters['q']) ?>">
      </div>
      <div class="col-md-3">
        <select name="status" class="form-select">
          <option value="">All statuses</option>
          <option value="active" <?= $filters['status'] === 'active' ? 'selected' : '' ?>>Active</option>
          <option value="abandoned" <?= $filters['status'] === 'abandoned' ? 'selected' : '' ?>>Abandoned</option>
          <option value="converted" <?= $filters['status'] === 'converted' ? 'selected' : '' ?>>Converted</option>
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
        <button type="submit" class="btn btn-primary w-100">Apply</button>
        <a href="/admin/carts/view" class="btn btn-secondary w-100">Reset</a>
      </div>
    </form>

    <div class="text-muted mb-2">Showing <?= $start ?>-<?= $end ?> of <?= $total ?> carts</div>

    <table class="table table-bordered table-striped align-middle text-center">
      <thead class="table-info text-dark">
        <tr>
          <th>Cart ID</th>
          <th>Customer</th>
          <th>Email</th>
          <th>Status</th>
          <th>Items</th>
          <th>Quantity</th>
          <th>Subtotal</th>
          <th>Updated</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
      <?php if (empty($carts)): ?>
        <tr><td colspan="9" class="text-muted">No carts found.</td></tr>
      <?php else: ?>
        <?php foreach ($carts as $cart): ?>
          <?php
            $status = $cart['status'];
            $badge = match ($status) {
              'active' => 'bg-success',
              'abandoned' => 'bg-warning text-dark',
              'converted' => 'bg-info',
              default => 'bg-secondary'
            };
          ?>
          <tr>
            <td>#<?= (int) $cart['id'] ?></td>
            <td><?= htmlspecialchars($cart['customer_name']) ?></td>
            <td><?= htmlspecialchars($cart['customer_email']) ?></td>
            <td><span class="badge <?= $badge ?>"><?= htmlspecialchars(ucfirst($status)) ?></span></td>
            <td><?= (int) $cart['total_items'] ?></td>
            <td><?= (int) $cart['total_quantity'] ?></td>
            <td>GH₵<?= number_format((float) $cart['sub_total'], 2) ?></td>
            <td><?= htmlspecialchars($cart['updated_at']) ?></td>
            <td>
              <a href="/admin/carts/show?id=<?= (int) $cart['id'] ?>" class="btn btn-sm btn-info text-white">
                <i class="fa fa-eye"></i>
              </a>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
      </tbody>
    </table>

    <?php if ($totalPages > 1): ?>
      <nav aria-label="Carts pagination">
        <ul class="pagination justify-content-center mb-0">
          <?php $prev = $queryBase; $prev['page'] = max(1, $page - 1); ?>
          <?php $next = $queryBase; $next['page'] = min($totalPages, $page + 1); ?>
          <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
            <a class="page-link" href="/admin/carts/view?<?= http_build_query($prev) ?>">Previous</a>
          </li>
          <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
            <?php $qp = $queryBase; $qp['page'] = $i; ?>
            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
              <a class="page-link" href="/admin/carts/view?<?= http_build_query($qp) ?>"><?= $i ?></a>
            </li>
          <?php endfor; ?>
          <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
            <a class="page-link" href="/admin/carts/view?<?= http_build_query($next) ?>">Next</a>
          </li>
        </ul>
      </nav>
    <?php endif; ?>
  </div>
</div>
