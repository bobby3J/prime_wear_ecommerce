<?php
/**
 * Admin Orders Listing View
 * -------------------------
 * This page is intentionally read-only and audit-focused.
 * It shows customer, totals, status, and quick navigation to order detail.
 */
$orders = $orders ?? [];
$filters = $filters ?? ['q' => '', 'status' => ''];
$pagination = $pagination ?? ['page' => 1, 'per_page' => 10, 'total' => 0, 'total_pages' => 1];

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
    <h3 class="text-center mb-4 text-info fw-bold">All Orders</h3>

    <form class="row g-3 mb-3" method="get" action="/admin/orders/view">
      <div class="col-md-5">
        <input
          type="text"
          name="q"
          class="form-control"
          placeholder="Search by order number, customer name, or email"
          value="<?= htmlspecialchars($filters['q']) ?>"
        >
      </div>
      <div class="col-md-3">
        <select name="status" class="form-select">
          <option value="">All statuses</option>
          <option value="pending" <?= $filters['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
          <option value="paid" <?= $filters['status'] === 'paid' ? 'selected' : '' ?>>Paid</option>
          <option value="shipped" <?= $filters['status'] === 'shipped' ? 'selected' : '' ?>>Shipped</option>
          <option value="delivered" <?= $filters['status'] === 'delivered' ? 'selected' : '' ?>>Delivered</option>
          <option value="cancelled" <?= $filters['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
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
        <a href="/admin/orders/view" class="btn btn-outline-secondary w-100">Reset</a>
      </div>
    </form>

    <div class="text-muted mb-2">Showing <?= $start ?>-<?= $end ?> of <?= $total ?> orders</div>

    <div class="table-responsive">
      <table class="table table-striped table-hover align-middle">
        <thead class="table-light">
          <tr>
            <th>#</th>
            <th>Order Number</th>
            <th>Customer</th>
            <th>Email</th>
            <th>Items</th>
            <th>Qty</th>
            <th>Total</th>
            <th>Status</th>
            <th>Created</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
        <?php if (empty($orders)): ?>
          <tr>
            <td colspan="10" class="text-center text-muted">No orders found.</td>
          </tr>
        <?php else: ?>
          <?php foreach ($orders as $order): ?>
            <?php
            $status = strtolower((string) ($order['status'] ?? 'pending'));
            $badgeClass = match ($status) {
                'paid', 'delivered' => 'bg-success',
                'pending' => 'bg-warning text-dark',
                'cancelled' => 'bg-danger',
                default => 'bg-info'
            };
            ?>
            <tr>
              <td><?= (int) $order['id'] ?></td>
              <td><?= htmlspecialchars($order['order_number']) ?></td>
              <td><?= htmlspecialchars($order['customer_name']) ?></td>
              <td><?= htmlspecialchars($order['customer_email']) ?></td>
              <td><?= (int) ($order['line_items_count'] ?? 0) ?></td>
              <td><?= (int) ($order['total_quantity'] ?? 0) ?></td>
              <td>$<?= number_format((float) $order['total_amount'], 2) ?></td>
              <td><span class="badge <?= $badgeClass ?>"><?= htmlspecialchars(ucfirst($status)) ?></span></td>
              <td><?= htmlspecialchars((string) ($order['created_at'] ?? '')) ?></td>
              <td>
                <a href="/admin/orders/show?id=<?= (int) $order['id'] ?>" class="btn btn-sm btn-outline-info">
                  <i class="fa fa-eye"></i>
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
      </table>
    </div>

    <?php if ($totalPages > 1): ?>
      <nav aria-label="Orders pagination">
        <ul class="pagination justify-content-center mb-0">
          <?php $prev = $queryBase; $prev['page'] = max(1, $page - 1); ?>
          <?php $next = $queryBase; $next['page'] = min($totalPages, $page + 1); ?>
          <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
            <a class="page-link" href="/admin/orders/view?<?= http_build_query($prev) ?>">Previous</a>
          </li>
          <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
            <?php $qp = $queryBase; $qp['page'] = $i; ?>
            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
              <a class="page-link" href="/admin/orders/view?<?= http_build_query($qp) ?>"><?= $i ?></a>
            </li>
          <?php endfor; ?>
          <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
            <a class="page-link" href="/admin/orders/view?<?= http_build_query($next) ?>">Next</a>
          </li>
        </ul>
      </nav>
    <?php endif; ?>
  </div>
</div>
