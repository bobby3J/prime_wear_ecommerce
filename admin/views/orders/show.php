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
?>

<div class="card border-0 shadow-sm">
  <div class="card-body">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h3 class="text-info fw-bold mb-0">Order Detail</h3>
      <a href="/admin/orders/view" class="btn btn-outline-secondary btn-sm">Back</a>
    </div>

    <div class="row g-3 mb-4">
      <div class="col-md-3"><div class="card border-0 shadow-sm"><div class="card-body"><strong>Order #</strong><div><?= htmlspecialchars($order['order_number']) ?></div></div></div></div>
      <div class="col-md-3"><div class="card border-0 shadow-sm"><div class="card-body"><strong>Status</strong><div><span class="badge <?= $statusBadge ?>"><?= htmlspecialchars(ucfirst($status)) ?></span></div></div></div></div>
      <div class="col-md-3"><div class="card border-0 shadow-sm"><div class="card-body"><strong>Total</strong><div>GH₵<?= number_format((float) $order['total_amount'], 2) ?></div></div></div></div>
      <div class="col-md-3"><div class="card border-0 shadow-sm"><div class="card-body"><strong>Created</strong><div><?= htmlspecialchars((string) ($order['created_at'] ?? '')) ?></div></div></div></div>
      <div class="col-md-4"><div class="card border-0 shadow-sm"><div class="card-body"><strong>Customer</strong><div><?= htmlspecialchars((string) ($order['customer_name'] ?? '')) ?></div></div></div></div>
      <div class="col-md-4"><div class="card border-0 shadow-sm"><div class="card-body"><strong>Email</strong><div><?= htmlspecialchars((string) ($order['customer_email'] ?? '')) ?></div></div></div></div>
      <div class="col-md-4"><div class="card border-0 shadow-sm"><div class="card-body"><strong>Customer ID</strong><div>#<?= (int) ($order['customer_id'] ?? 0) ?></div></div></div></div>
    </div>

    <h5 class="mb-3">Delivery Details Snapshot</h5>
    <div class="card border-0 shadow-sm mb-4">
      <div class="card-body">
        <div><strong>Full Name:</strong> <?= htmlspecialchars((string) ($order['full_name'] ?? '-')) ?></div>
        <div><strong>Phone:</strong> <?= htmlspecialchars((string) ($order['phone'] ?? '-')) ?></div>
        <div><strong>Street Address:</strong> <?= htmlspecialchars((string) ($order['street_address'] ?? '-')) ?></div>
      </div>
    </div>

    <h5 class="mb-3">Order Items</h5>
    <table class="table table-bordered table-striped align-middle text-center mb-4">
      <thead class="table-info text-dark">
        <tr>
          <th>#</th>
          <th>Product</th>
          <th>Quantity</th>
          <th>Price At Purchase</th>
          <th>Line Total</th>
        </tr>
      </thead>
      <tbody>
      <?php if (empty($items)): ?>
        <tr><td colspan="5" class="text-muted">No order items found.</td></tr>
      <?php else: ?>
        <?php foreach ($items as $item): ?>
          <tr>
            <td><?= (int) $item['id'] ?></td>
            <td><?= htmlspecialchars((string) ($item['product_name'] ?? '')) ?></td>
            <td><?= (int) $item['quantity'] ?></td>
            <td>GH₵<?= number_format((float) $item['price_at_purchase'], 2) ?></td>
            <td>GH₵<?= number_format((float) $item['line_total'], 2) ?></td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
      </tbody>
    </table>

    <h5 class="mb-3">Payments</h5>
    <table class="table table-bordered table-striped align-middle text-center">
      <thead class="table-info text-dark">
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
        <tr><td colspan="6" class="text-muted">No payments found for this order.</td></tr>
      <?php else: ?>
        <?php foreach ($payments as $payment): ?>
          <?php
          $pStatus = strtolower((string) ($payment['status'] ?? 'pending'));
          $pBadge = match ($pStatus) {
              'successful' => 'bg-success',
              'failed' => 'bg-danger',
              default => 'bg-warning text-dark'
          };
          ?>
          <tr>
            <td><?= (int) $payment['id'] ?></td>
            <td><?= htmlspecialchars((string) $payment['method']) ?></td>
            <td><span class="badge <?= $pBadge ?>"><?= htmlspecialchars(ucfirst($pStatus)) ?></span></td>
            <td>GH₵<?= number_format((float) $payment['amount'], 2) ?></td>
            <td><?= htmlspecialchars((string) ($payment['transaction_ref'] ?? '-')) ?></td>
            <td><?= htmlspecialchars((string) ($payment['created_at'] ?? '')) ?></td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
