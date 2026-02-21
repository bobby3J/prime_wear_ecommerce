<?php
$cart = $cart ?? null;
$items = $items ?? [];
$status = $status ?? 'active';
$subTotal = (float) ($sub_total ?? 0);

if (!$cart) {
    echo '<div class="alert alert-danger">Cart not found.</div>';
    return;
}

$statusBadge = match ($status) {
    'active' => 'bg-success',
    'abandoned' => 'bg-warning text-dark',
    'converted' => 'bg-info',
    default => 'bg-secondary'
};
?>

<div class="card border-0 shadow-sm">
  <div class="card-body">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h3 class="text-info fw-bold mb-0">Cart Detail</h3>
      <a href="/admin/carts/view" class="btn btn-outline-secondary btn-sm">Back</a>
    </div>

    <div class="row g-3 mb-4">
      <div class="col-md-3"><div class="card border-0 shadow-sm"><div class="card-body"><strong>Cart ID</strong><div>#<?= (int) $cart['id'] ?></div></div></div></div>
      <div class="col-md-3"><div class="card border-0 shadow-sm"><div class="card-body"><strong>Customer</strong><div><?= htmlspecialchars($cart['customer_name']) ?></div></div></div></div>
      <div class="col-md-3"><div class="card border-0 shadow-sm"><div class="card-body"><strong>Email</strong><div><?= htmlspecialchars($cart['customer_email']) ?></div></div></div></div>
      <div class="col-md-3"><div class="card border-0 shadow-sm"><div class="card-body"><strong>Status</strong><div><span class="badge <?= $statusBadge ?>"><?= htmlspecialchars(ucfirst($status)) ?></span></div></div></div></div>
      <div class="col-md-3"><div class="card border-0 shadow-sm"><div class="card-body"><strong>Created</strong><div><?= htmlspecialchars($cart['created_at']) ?></div></div></div></div>
      <div class="col-md-3"><div class="card border-0 shadow-sm"><div class="card-body"><strong>Last Updated</strong><div><?= htmlspecialchars($cart['updated_at']) ?></div></div></div></div>
      <div class="col-md-3"><div class="card border-0 shadow-sm"><div class="card-body"><strong>Subtotal</strong><div>GH₵<?= number_format($subTotal, 2) ?></div></div></div></div>
      <div class="col-md-3"><div class="card border-0 shadow-sm"><div class="card-body"><strong>Items</strong><div><?= count($items) ?></div></div></div></div>
    </div>

    <h5 class="mb-3">Cart Items (Read-only Audit Trail)</h5>
    <table class="table table-bordered table-striped align-middle text-center">
      <thead class="table-info text-dark">
        <tr>
          <th>#</th>
          <th>Product</th>
          <th>Price</th>
          <th>Quantity</th>
          <th>Line Total</th>
          <th>Image</th>
        </tr>
      </thead>
      <tbody>
      <?php if (empty($items)): ?>
        <tr><td colspan="6" class="text-muted">No cart items found.</td></tr>
      <?php else: ?>
        <?php foreach ($items as $item): ?>
          <tr>
            <td><?= (int) $item['id'] ?></td>
            <td><?= htmlspecialchars($item['name']) ?></td>
            <td>GH₵<?= number_format((float) $item['price'], 2) ?></td>
            <td><?= (int) $item['quantity'] ?></td>
            <td>GH₵<?= number_format((float) $item['line_total'], 2) ?></td>
            <td>
              <?php if (!empty($item['image_url'])): ?>
                <img src="<?= htmlspecialchars($item['image_url']) ?>" width="50" class="rounded" alt="product">
              <?php else: ?>
                <span class="text-muted">No image</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

