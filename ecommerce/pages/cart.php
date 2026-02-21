<?php
ob_start();
?>
<!--
Cart page flow:
1) cart-page.js checks authenticated customer via /shared/api/auth/me.php.
2) If not authenticated, user is redirected to home (login can be opened from navbar).
3) If authenticated, cart-page.js loads /shared/api/cart/get.php.
4) Update/remove actions call:
   - /shared/api/cart/update.php
   - /shared/api/cart/remove.php
5) Checkout requires confirmation:
   - customer enters name, phone, street address
   - clicks confirm button
   - only then can payment action be triggered
6) Simulated payment endpoint creates:
   - orders
   - order_items
   - order_delivery_details
   - payments
7) After payment, cart is cleared, and badge sync event is emitted.
-->
<div class="container my-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">My Cart</h4>
    <a href="/ecommerce/index.php?page=home" class="btn btn-sm btn-outline-secondary">Continue Shopping</a>
  </div>

  <div class="card shadow-sm mb-3">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-striped align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th>Product</th>
              <th>Unit Price</th>
              <th>Quantity</th>
              <th>Line Total</th>
              <th></th>
              <th></th>
            </tr>
          </thead>
          <tbody id="cartItemsBody">
            <tr>
              <td colspan="6" class="text-center text-muted py-4">Loading cart...</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="card shadow-sm">
    <div class="card-body" id="cartSummary">
      <div><strong>Total Items:</strong> 0</div>
      <div><strong>Total Quantity:</strong> 0</div>
      <div><strong>Sub Total:</strong> GH₵0.00</div>
    </div>
  </div>

  <div class="card shadow-sm mt-4">
    <div class="card-body">
      <h5 class="mb-3">Checkout Confirmation</h5>
      <p class="text-muted mb-3">
        Enter delivery details and confirm before choosing payment.
      </p>

      <div class="row g-3">
        <div class="col-md-4">
          <label class="form-label" for="checkoutName">Full Name</label>
          <input type="text" class="form-control" id="checkoutName" placeholder="John Doe">
        </div>
        <div class="col-md-4">
          <label class="form-label" for="checkoutPhone">Phone</label>
          <input type="text" class="form-control" id="checkoutPhone" placeholder="+233 24 000 0000">
        </div>
        <div class="col-md-4">
          <label class="form-label" for="checkoutStreetAddress">Street Address</label>
          <input type="text" class="form-control" id="checkoutStreetAddress" placeholder="Spintex Road, Accra">
        </div>
      </div>

      <div class="d-flex gap-2 mt-3">
        <button class="btn btn-primary" id="checkoutConfirmBtn" type="button">Confirm Details</button>
        <span class="text-muted align-self-center" id="checkoutConfirmHint">Confirm to unlock payment.</span>
      </div>

      <hr class="my-4">

      <h6 class="mb-3">Payment (Simulated for Testing)</h6>
      <div class="row g-3">
        <div class="col-md-3">
          <label class="form-label" for="checkoutPaymentMethod">Method</label>
          <select class="form-select" id="checkoutPaymentMethod" disabled>
            <option value="momo">MoMo</option>
            <option value="bank">Bank</option>
            <option value="cash_on_delivery">Cash On Delivery</option>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label" for="checkoutSimulateResult">Simulate Result</label>
          <select class="form-select" id="checkoutSimulateResult" disabled>
            <option value="successful">Successful</option>
            <option value="pending">Pending</option>
            <option value="failed">Failed</option>
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label" for="checkoutTransactionRef">Transaction Ref (optional)</label>
          <input type="text" class="form-control" id="checkoutTransactionRef" placeholder="Auto-generated if empty" disabled>
        </div>
        <div class="col-md-2 d-flex align-items-end">
          <button class="btn btn-success w-100" id="checkoutPayBtn" type="button" disabled>Pay Now</button>
        </div>
      </div>

      <div class="mt-3" id="checkoutStatus"></div>
    </div>
  </div>
</div>

<script type="module" src="/ecommerce/assets/js/cart-page.js"></script>
<?php
return ob_get_clean();
?>
