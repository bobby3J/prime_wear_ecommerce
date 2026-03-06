<?php
ob_start();
?>
<!--
Payment page flow:
1) Validates authenticated customer and checkout confirmation state.
2) Displays delivery snapshot + order summary.
3) Initiates provider payment (or COD pending flow).
-->
<div class="container my-4 payment-shell">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0 payment-title"><i class="fa-solid fa-wallet me-2"></i>Payment</h4>
    <a href="/ecommerce/index.php?page=checkout" class="btn btn-sm btn-outline-secondary">Back to Checkout</a>
  </div>

  <div class="row g-4">
    <div class="col-lg-8">
      <div class="payment-intro mb-3">
        Confirm your delivery snapshot and order totals before payment.
      </div>

      <div class="card shadow-sm border-0 payment-card mb-3">
        <div class="card-body">
          <h5 class="mb-3 payment-section-title"><i class="fa-solid fa-location-dot me-2"></i>Delivery Details</h5>
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

      <div class="card shadow-sm border-0 payment-card">
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-striped align-middle mb-0 payment-items-table">
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

      <div class="card shadow-sm border-0 payment-card mt-3">
        <div class="card-body" id="paymentSummary">
          <div class="row g-2 payment-summary-grid">
            <div class="col-md-4">
              <div class="p-3 rounded border bg-light h-100 payment-metric">
                <div class="small text-muted payment-metric-label">Total Items</div>
                <div class="fs-5 fw-bold payment-metric-value">0</div>
              </div>
            </div>
            <div class="col-md-4">
              <div class="p-3 rounded border bg-light h-100 payment-metric">
                <div class="small text-muted payment-metric-label">Quantity</div>
                <div class="fs-5 fw-bold payment-metric-value">0</div>
              </div>
            </div>
            <div class="col-md-4">
              <div class="p-3 rounded border bg-light h-100 payment-metric">
                <div class="small text-muted payment-metric-label">Sub Total</div>
                <div class="fs-5 fw-bold text-dark payment-metric-value">GH&#8373;0.00</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-lg-4">
      <div class="card shadow-sm border-0 payment-card">
        <div class="card-body">
          <h5 class="mb-3 payment-section-title"><i class="fa-solid fa-credit-card me-2"></i>Payment Method</h5>

          <div class="mb-3">
            <label class="form-label" for="paymentMethod">Method</label>
            <select class="form-select d-none" id="paymentMethod" aria-hidden="true" tabindex="-1">
              <option value="mtn_momo">MTN MoMo</option>
              <option value="telecel_cash">Telecel Cash</option>
              <option value="bank">Bank</option>
              <option value="cash_on_delivery">Cash On Delivery</option>
            </select>
            <div class="payment-provider-row d-flex flex-wrap gap-2 mt-2" id="paymentProviderRow">
              <button type="button" class="payment-provider-chip" data-provider-chip="mtn_momo">
                <img src="/ecommerce/assets/images/mtn2.png" alt="MTN MoMo" class="payment-provider-chip-img">
                <span>MTN MoMo</span>
              </button>
              <button type="button" class="payment-provider-chip" data-provider-chip="telecel_cash">
                <img src="/ecommerce/assets/images/telece_cash.png" alt="Telecel Cash" class="payment-provider-chip-img">
                <span>Telecel Cash</span>
              </button>
              <button type="button" class="payment-provider-chip" data-provider-chip="bank">
                <i class="fa-solid fa-building-columns"></i>
                <span>Bank</span>
              </button>
              <button type="button" class="payment-provider-chip" data-provider-chip="cash_on_delivery">
                <i class="fa-solid fa-truck"></i>
                <span>Cash On Delivery</span>
              </button>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label" for="paymentTransactionRef">Transaction Ref (optional)</label>
            <input type="text" class="form-control" id="paymentTransactionRef" placeholder="Auto-generated if empty">
          </div>

          <div class="mb-3" id="paymentPayerPhoneWrap">
            <label class="form-label" for="paymentPayerPhone" id="paymentPayerPhoneLabel">Payer Number (MTN MoMo)</label>
            <input type="text" class="form-control" id="paymentPayerPhone" placeholder="e.g. 024xxxxxxx">
            <div class="form-text" id="paymentPayerPhoneHint">
              Required for MTN MoMo and Telecel Cash prompt flow.
            </div>
          </div>

          <div class="alert alert-light border small mb-3">
            <i class="fa-solid fa-circle-info me-2"></i>
            MTN MoMo, Telecel Cash, and Bank run provider-initiation flow. COD creates a pending order and you pay on delivery.
          </div>

          <div class="alert alert-light border small mb-3" id="paymentCollectionDestination">
            <i class="fa-solid fa-building-columns me-2"></i>
            Business collection account details will appear here before payment.
          </div>

          <div class="d-grid">
            <button class="btn btn-gold-soft" id="paymentPayBtn" type="button" disabled>
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
