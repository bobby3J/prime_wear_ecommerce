<?php
/**
 * Admin Payments Listing View
 * ---------------------------
 * Read-only payment monitoring screen with filter/search controls.
 */
$payments = $payments ?? [];
$filters = $filters ?? ['q' => '', 'status' => '', 'method' => ''];
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
    'method' => $filters['method'] !== '' ? $filters['method'] : null,
    'per_page' => $perPage !== 10 ? $perPage : null,
], static fn($v) => $v !== null && $v !== '');
?>

<div class="card border-0 shadow-sm">
  <div class="card-body">
    <h3 class="text-center mb-4 text-info fw-bold">All Payments</h3>

    <form class="row g-3 mb-3" method="get" action="/admin/payments/view">
      <div class="col-md-4">
        <input
          type="text"
          name="q"
          class="form-control"
          placeholder="Search customer, order number, or reference"
          value="<?= htmlspecialchars($filters['q']) ?>"
        >
      </div>
      <div class="col-md-2">
        <select name="status" class="form-select">
          <option value="">All statuses</option>
          <option value="pending" <?= $filters['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
          <option value="successful" <?= $filters['status'] === 'successful' ? 'selected' : '' ?>>Successful</option>
          <option value="failed" <?= $filters['status'] === 'failed' ? 'selected' : '' ?>>Failed</option>
        </select>
      </div>
      <div class="col-md-2">
        <select name="method" class="form-select">
          <option value="">All methods</option>
          <option value="mobile money" <?= $filters['method'] === 'mobile money' ? 'selected' : '' ?>>Mobile Money</option>
          <option value="card" <?= $filters['method'] === 'card' ? 'selected' : '' ?>>Card / Bank</option>
          <option value="cash on delivery" <?= $filters['method'] === 'cash on delivery' ? 'selected' : '' ?>>Cash On Delivery</option>
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
        <a href="/admin/payments/view" class="btn btn-outline-secondary w-100">Reset</a>
      </div>
    </form>

    <div class="text-muted mb-2">Showing <?= $start ?>-<?= $end ?> of <?= $total ?> payments</div>

    <div class="table-responsive">
      <table class="table table-striped table-hover align-middle">
        <thead class="table-light">
          <tr>
            <th>#</th>
            <th>Payment ID</th>
            <th>Order</th>
            <th>Customer</th>
            <th>Amount</th>
            <th>Method</th>
            <th>Status</th>
            <th>Reference</th>
            <th>Date</th>
          </tr>
        </thead>
        <tbody>
        <?php if (empty($payments)): ?>
          <tr><td colspan="9" class="text-center text-muted">No payments found.</td></tr>
        <?php else: ?>
          <?php foreach ($payments as $payment): ?>
            <?php
            $status = strtolower((string) ($payment['status'] ?? 'pending'));
            $badgeClass = match ($status) {
                'successful' => 'bg-success',
                'failed' => 'bg-danger',
                default => 'bg-warning text-dark'
            };
            ?>
            <tr>
              <td><?= (int) $payment['id'] ?></td>
              <td>PAY-<?= (int) $payment['id'] ?></td>
              <td>
                <a href="/admin/orders/show?id=<?= (int) $payment['order_id'] ?>" class="text-decoration-none">
                  <?= htmlspecialchars((string) ($payment['order_number'] ?? ('#' . (int) $payment['order_id']))) ?>
                </a>
              </td>
              <td>
                <div><?= htmlspecialchars((string) ($payment['customer_name'] ?? '')) ?></div>
                <small class="text-muted"><?= htmlspecialchars((string) ($payment['customer_email'] ?? '')) ?></small>
              </td>
              <td>$<?= number_format((float) $payment['amount'], 2) ?></td>
              <td><?= htmlspecialchars((string) $payment['method']) ?></td>
              <td><span class="badge <?= $badgeClass ?>"><?= htmlspecialchars(ucfirst($status)) ?></span></td>
              <td><?= htmlspecialchars((string) ($payment['transaction_ref'] ?? '-')) ?></td>
              <td><?= htmlspecialchars((string) ($payment['created_at'] ?? '')) ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
      </table>
    </div>

    <?php if ($totalPages > 1): ?>
      <nav aria-label="Payments pagination">
        <ul class="pagination justify-content-center mb-0">
          <?php $prev = $queryBase; $prev['page'] = max(1, $page - 1); ?>
          <?php $next = $queryBase; $next['page'] = min($totalPages, $page + 1); ?>
          <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
            <a class="page-link" href="/admin/payments/view?<?= http_build_query($prev) ?>">Previous</a>
          </li>
          <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
            <?php $qp = $queryBase; $qp['page'] = $i; ?>
            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
              <a class="page-link" href="/admin/payments/view?<?= http_build_query($qp) ?>"><?= $i ?></a>
            </li>
          <?php endfor; ?>
          <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
            <a class="page-link" href="/admin/payments/view?<?= http_build_query($next) ?>">Next</a>
          </li>
        </ul>
      </nav>
    <?php endif; ?>
  </div>
</div>
