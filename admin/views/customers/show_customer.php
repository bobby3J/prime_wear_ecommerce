<?php
$customer = $customer ?? null;
$summary = $summary ?? ['carts_count' => 0, 'orders_count' => 0, 'payments_count' => 0];
$carts = $carts ?? [];
if (!$customer) {
    echo '<div class="alert alert-danger">Customer not found.</div>';
    return;
}
?>

<div class="card border-0 shadow-sm">
  <div class="card-body">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h3 class="text-info fw-bold mb-0">Customer Profile</h3>
      <a href="/admin/customers/view" class="btn btn-outline-secondary btn-sm">Back</a>
    </div>

    <div class="row g-3 mb-4">
      <div class="col-md-4"><div class="card border-0 shadow-sm"><div class="card-body"><strong>Name</strong><div><?= htmlspecialchars($customer['name']) ?></div></div></div></div>
      <div class="col-md-4"><div class="card border-0 shadow-sm"><div class="card-body"><strong>Email</strong><div><?= htmlspecialchars($customer['email']) ?></div></div></div></div>
      <div class="col-md-4"><div class="card border-0 shadow-sm"><div class="card-body"><strong>Status</strong><div><?= htmlspecialchars(ucfirst($customer['status'])) ?></div></div></div></div>
      <div class="col-md-4"><div class="card border-0 shadow-sm"><div class="card-body"><strong>Carts</strong><div class="display-6"><?= (int) $summary['carts_count'] ?></div></div></div></div>
      <div class="col-md-4"><div class="card border-0 shadow-sm"><div class="card-body"><strong>Orders</strong><div class="display-6"><?= (int) $summary['orders_count'] ?></div></div></div></div>
      <div class="col-md-4"><div class="card border-0 shadow-sm"><div class="card-body"><strong>Payments</strong><div class="display-6"><?= (int) $summary['payments_count'] ?></div></div></div></div>
    </div>

    <h5 class="mb-3">Cart History (Read-only)</h5>
    <table class="table table-bordered table-striped align-middle text-center">
      <thead class="table-info text-dark">
        <tr>
          <th>Cart ID</th>
          <th>Created</th>
          <th>Last Updated</th>
          <th>Total Items</th>
          <th>Total Quantity</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
      <?php if (empty($carts)): ?>
        <tr><td colspan="6" class="text-muted">No carts found for this customer.</td></tr>
      <?php else: ?>
        <?php foreach ($carts as $cart): ?>
          <tr>
            <td>#<?= (int) $cart['id'] ?></td>
            <td><?= htmlspecialchars($cart['created_at']) ?></td>
            <td><?= htmlspecialchars($cart['updated_at']) ?></td>
            <td><?= (int) $cart['total_items'] ?></td>
            <td><?= (int) $cart['total_quantity'] ?></td>
            <td>
              <a href="/admin/carts/show?id=<?= (int) $cart['id'] ?>" class="btn btn-sm btn-outline-info">
                <i class="fa fa-eye"></i>
              </a>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

