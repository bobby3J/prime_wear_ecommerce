<?php
ob_start();
?>
<!--
Payment page flow:
1) Validates authenticated customer and checkout confirmation state.
2) Displays delivery snapshot + order summary.
3) Executes simulated payment.
-->
<div class="container my-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="fa-solid fa-wallet text-info me-2"></i>Payment</h4>
    <a href="/ecommerce/index.php?page=checkout" class="btn btn-sm btn-outline-secondary">Back to Checkout</a>
  </div>

  <div class="row g-4">
    <div class="col-lg-8">
      <div class="alert alert-info mb-3">
        <i class="fa-solid fa-shield-halved me-2"></i>
        Confirm your delivery snapshot and order totals before payment.
      </div>

      <div class="card shadow-sm border-0 mb-3">
        <div class="card-body">
          <h5 class="mb-3"><i class="fa-solid fa-location-dot text-info me-2"></i>Delivery Details</h5>
          <div class="row g-3">
            <div class="col-md-4">
              <div class="p-3 rounded border bg-light h-100">
                <div class="small text-muted mb-1"><i class="fa-solid fa-user me-1"></i>Full Name</div>
                <div class="fw-semibold" id="paymentDeliveryName">-</div>
              </div>
            </div>
            <div class="col-md-4">
              <div class="p-3 rounded border bg-light h-100">
                <div class="small text-muted mb-1"><i class="fa-solid fa-phone me-1"></i>Phone</div>
                <div class="fw-semibold" id="paymentDeliveryPhone">-</div>
              </div>
            </div>
            <div class="col-md-4">
              <div class="p-3 rounded border bg-light h-100">
                <div class="small text-muted mb-1"><i class="fa-solid fa-map-pin me-1"></i>Address</div>
                <div class="fw-semibold" id="paymentDeliveryStreet">-</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="card shadow-sm border-0">
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-striped align-middle mb-0">
              <thead class="table-light">
                <tr>
                  <th>Product</th>
                  <th>Unit Price</th>
                  <th>Quantity</th>
                  <th>Line Total</th>
                </tr>
              </thead>
              <tbody id="paymentItemsBody">
                <tr>
                  <td colspan="4" class="text-center text-muted py-4">Loading payment summary...</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <div class="card shadow-sm border-0 mt-3">
        <div class="card-body" id="paymentSummary">
          <div class="row g-2">
            <div class="col-md-4">
              <div class="p-3 rounded border bg-light h-100">
                <div class="small text-muted"><i class="fa-solid fa-box me-1 text-info"></i>Total Items</div>
                <div class="fs-5 fw-bold">0</div>
              </div>
            </div>
            <div class="col-md-4">
              <div class="p-3 rounded border bg-light h-100">
                <div class="small text-muted"><i class="fa-solid fa-layer-group me-1 text-info"></i>Quantity</div>
                <div class="fs-5 fw-bold">0</div>
              </div>
            </div>
            <div class="col-md-4">
              <div class="p-3 rounded border bg-light h-100">
                <div class="small text-muted"><i class="fa-solid fa-coins me-1 text-warning"></i>Sub Total</div>
                <div class="fs-5 fw-bold text-dark">GH&#8373;0.00</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-lg-4">
      <div class="card shadow-sm border-0">
        <div class="card-body">
          <h5 class="mb-3"><i class="fa-solid fa-credit-card text-info me-2"></i>Payment Method</h5>

          <div class="mb-3">
            <label class="form-label" for="paymentMethod">Method</label>
            <select class="form-select" id="paymentMethod">
              <option value="momo">MoMo</option>
              <option value="bank">Bank</option>
              <option value="cash_on_delivery">Cash On Delivery</option>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label" for="paymentSimulateResult">Simulate Result</label>
            <select class="form-select" id="paymentSimulateResult">
              <option value="successful">Successful</option>
              <option value="pending">Pending</option>
              <option value="failed">Failed</option>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label" for="paymentTransactionRef">Transaction Ref (optional)</label>
            <input type="text" class="form-control" id="paymentTransactionRef" placeholder="Auto-generated if empty">
          </div>

          <div class="alert alert-light border small mb-3">
            <i class="fa-solid fa-circle-info text-info me-2"></i>
            Payment is simulated for workflow testing and admin-side validation.
          </div>

          <div class="d-grid">
            <button class="btn btn-success" id="paymentPayBtn" type="button" disabled>
              <i class="fa-solid fa-lock me-2"></i>Pay Now (GH&#8373;0.00)
            </button>
          </div>

          <div class="mt-3" id="paymentStatus"></div>
        </div>
      </div>
    </div>
  </div>
</div>

<script type="module" src="/ecommerce/assets/js/payment-page.js"></script>
<?php
return ob_get_clean();
?>
