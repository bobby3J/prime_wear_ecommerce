<?php
/**
 * Admin Order Detail View
 * -----------------------
 * Read-only audit page for:
 * - order summary/status
 * - customer snapshot
 * - delivery details snapshot
 * - line items
 * - payment attempts
 */
$order = $order ?? null;
$items = $items ?? [];
$payments = $payments ?? [];

if (!$order) {
    echo '<div class="alert alert-danger">Order not found.</div>';
    return;
}

$status = strtolower((string) ($order['status'] ?? 'pending'));
$statusBadge = match ($status) {
    'paid', 'delivered' => 'bg-success',
    'pending' => 'bg-warning text-dark',
    'cancelled' => 'bg-danger',
    default => 'bg-info'
};

$itemCount = count($items);
$totalQuantity = array_reduce(
    $items,
    static fn(int $carry, array $item): int => $carry + (int) ($item['quantity'] ?? 0),
    0
);
$successfulPayments = array_reduce(
    $payments,
    static fn(int $carry, array $payment): int => $carry + (strtolower((string) ($payment['status'] ?? '')) === 'successful' ? 1 : 0),
    0
);

$formatDateTime = static function ($value): string {
    $value = (string) $value;
    if ($value === '') {
        return '-';
    }

    try {
        return (new DateTimeImmutable($value))->format('M d, Y h:i A');
    } catch (Throwable) {
        return $value;
    }
};
?>

<div class="order-detail-shell">
  <div class="order-detail-hero mb-3">
    <div>
      <div class="text-uppercase order-detail-eyebrow">Order Detail</div>
      <div class="d-flex flex-wrap gap-2 align-items-center order-heading-line">
        <span class="order-id-pill">#<?= htmlspecialchars((string) ($order['order_number'] ?? '')) ?></span>
        <span class="badge <?= $statusBadge ?>"><?= htmlspecialchars(ucfirst($status)) ?></span>
        <span class="order-chip">Created: <?= htmlspecialchars($formatDateTime($order['created_at'] ?? '')) ?></span>
      </div>
    </div>
    <a href="/admin/orders/view" class="btn btn-outline-secondary btn-sm order-back-btn">Back To Orders</a>
  </div>

  <div class="row g-2 mb-2">
    <div class="col-sm-6 col-xl-3">
      <div class="order-stat-card">
        <div class="order-stat-label">Order Total</div>
        <div class="order-stat-value">GH&#8373;<?= number_format((float) ($order['total_amount'] ?? 0), 2) ?></div>
      </div>
    </div>
    <div class="col-sm-6 col-xl-3">
      <div class="order-stat-card">
        <div class="order-stat-label">Line Items</div>
        <div class="order-stat-value"><?= $itemCount ?></div>
      </div>
    </div>
    <div class="col-sm-6 col-xl-3">
      <div class="order-stat-card">
        <div class="order-stat-label">Total Quantity</div>
        <div class="order-stat-value"><?= $totalQuantity ?></div>
      </div>
    </div>
    <div class="col-sm-6 col-xl-3">
      <div class="order-stat-card">
        <div class="order-stat-label">Successful Payments</div>
        <div class="order-stat-value"><?= $successfulPayments ?></div>
      </div>
    </div>
  </div>

  <div class="row g-2 mb-2">
    <div class="col-lg-6">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body">
          <h5 class="order-section-title">Customer Snapshot</h5>
          <dl class="order-meta-list">
            <dt>Name</dt>
            <dd><?= htmlspecialchars((string) ($order['customer_name'] ?? '-')) ?></dd>
            <dt>Email</dt>
            <dd><?= htmlspecialchars((string) ($order['customer_email'] ?? '-')) ?></dd>
            <dt>Customer ID</dt>
            <dd>#<?= (int) ($order['customer_id'] ?? 0) ?></dd>
          </dl>
        </div>
      </div>
    </div>
    <div class="col-lg-6">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body">
          <h5 class="order-section-title">Delivery Details</h5>
          <dl class="order-meta-list">
            <dt>Full Name</dt>
            <dd><?= htmlspecialchars((string) ($order['full_name'] ?? '-')) ?></dd>
            <dt>Phone</dt>
            <dd><?= htmlspecialchars((string) ($order['phone'] ?? '-')) ?></dd>
            <dt>Street Address</dt>
            <dd><?= htmlspecialchars((string) ($order['street_address'] ?? '-')) ?></dd>
          </dl>
        </div>
      </div>
    </div>
  </div>

  <div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
      <h5 class="order-section-title">Order Items</h5>
      <div class="table-responsive">
        <table class="table align-middle text-center order-detail-table mb-0">
          <thead>
            <tr>
              <th>#</th>
              <th class="text-start">Product</th>
              <th>Qty</th>
              <th>Price At Purchase</th>
              <th>Line Total</th>
            </tr>
          </thead>
          <tbody>
          <?php if (empty($items)): ?>
            <tr><td colspan="5" class="text-muted py-4">No order items found.</td></tr>
          <?php else: ?>
            <?php foreach ($items as $index => $item): ?>
              <tr>
                <td><?= $index + 1 ?></td>
                <td class="text-start"><?= htmlspecialchars((string) ($item['product_name'] ?? '-')) ?></td>
                <td><?= (int) ($item['quantity'] ?? 0) ?></td>
                <td>GH&#8373;<?= number_format((float) ($item['price_at_purchase'] ?? 0), 2) ?></td>
                <td class="fw-semibold">GH&#8373;<?= number_format((float) ($item['line_total'] ?? 0), 2) ?></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="card border-0 shadow-sm">
    <div class="card-body">
      <h5 class="order-section-title">Payments</h5>
      <div class="table-responsive">
        <table class="table align-middle text-center order-detail-table mb-0">
          <thead>
            <tr>
              <th>#</th>
              <th>Method</th>
              <th>Status</th>
              <th>Amount</th>
              <th>Reference</th>
              <th>Created</th>
            </tr>
          </thead>
          <tbody>
          <?php if (empty($payments)): ?>
            <tr><td colspan="6" class="text-muted py-4">No payments found for this order.</td></tr>
          <?php else: ?>
            <?php foreach ($payments as $index => $payment): ?>
              <?php
              $pStatus = strtolower((string) ($payment['status'] ?? 'pending'));
              $pBadge = match ($pStatus) {
                  'successful' => 'bg-success',
                  'failed' => 'bg-danger',
                  default => 'bg-warning text-dark'
              };
              ?>
              <tr>
                <td><?= $index + 1 ?></td>
                <td><?= htmlspecialchars((string) ($payment['method'] ?? '-')) ?></td>
                <td><span class="badge <?= $pBadge ?>"><?= htmlspecialchars(ucfirst($pStatus)) ?></span></td>
                <td>GH&#8373;<?= number_format((float) ($payment['amount'] ?? 0), 2) ?></td>
                <td class="text-break"><?= htmlspecialchars((string) ($payment['transaction_ref'] ?? '-')) ?></td>
                <td><?= htmlspecialchars($formatDateTime($payment['created_at'] ?? '')) ?></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
