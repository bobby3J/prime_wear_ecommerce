<?php
ob_start();
?>
<!--
Checkout page flow:
1) Validates authenticated customer.
2) Collects delivery details.
3) Opens review popup with order + delivery snapshot.
4) Confirms details only from popup, then proceeds to payment.
-->
<div class="container my-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Checkout</h4>
    <a href="/ecommerce/index.php?page=cart" class="btn btn-sm btn-outline-secondary">Back to Cart</a>
  </div>

  <div class="row g-4">
    <div class="col-lg-7">
      <div class="card shadow-sm border-0 h-100">
        <div class="card-body">
          <h5 class="mb-3 text-info fw-bold"><i class="fa-solid fa-circle-check me-2"></i>Review Before Confirmation</h5>
          <p class="text-muted mb-3">
            Your delivery details will only be confirmed after you review the popup summary and click confirm.
          </p>

          <div class="row g-3">
            <div class="col-md-6">
              <div class="p-3 rounded border bg-light h-100">
                <div class="d-flex align-items-center mb-2">
                  <span class="me-2 text-info"><i class="fa-solid fa-truck-fast"></i></span>
                  <strong>Delivery Safety</strong>
                </div>
                <small class="text-muted">Verify address and phone before final confirmation to avoid wrong deliveries.</small>
              </div>
            </div>
            <div class="col-md-6">
              <div class="p-3 rounded border bg-light h-100">
                <div class="d-flex align-items-center mb-2">
                  <span class="me-2 text-info"><i class="fa-solid fa-receipt"></i></span>
                  <strong>Order Accuracy</strong>
                </div>
                <small class="text-muted">Check all items, quantities, and totals in the review popup before payment.</small>
              </div>
            </div>
          </div>

          <div class="alert alert-info mt-4 mb-0">
            <i class="fa-solid fa-circle-info me-2"></i>
            Click <strong>Review Order Summary</strong> to open the confirmation popup.
          </div>
        </div>
      </div>
    </div>

    <div class="col-lg-5">
      <div class="card shadow-sm">
        <div class="card-body">
          <h5 class="mb-3">Delivery Details</h5>
          <p class="text-muted mb-3 small">Enter details, review popup, then confirm.</p>

          <div class="mb-3">
            <label class="form-label" for="checkoutName">Full Name</label>
            <input type="text" class="form-control" id="checkoutName" placeholder="John Doe">
          </div>

          <div class="mb-3">
            <label class="form-label" for="checkoutPhone">Phone</label>
            <input type="text" class="form-control" id="checkoutPhone" placeholder="+233 24 000 0000">
          </div>

          <div class="mb-3">
            <label class="form-label" for="checkoutStreetAddress">Street Address</label>
            <input type="text" class="form-control" id="checkoutStreetAddress" placeholder="Spintex Road, Accra">
          </div>

          <div class="d-grid gap-2">
            <button class="btn btn-outline-primary" id="checkoutReviewBtn" type="button">
              Review Order Summary
            </button>
            <a class="btn btn-outline-primary d-none" id="checkoutToPaymentBtn" href="/ecommerce/index.php?page=payment">
              Continue to Payment
            </a>
          </div>

          <div class="mt-3" id="checkoutStatus"></div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="checkoutReviewModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-info text-white">
        <h5 class="modal-title">
          <i class="fa-solid fa-file-invoice-dollar me-2"></i>Review Order & Delivery Details
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="row g-3 mb-3">
          <div class="col-md-6">
            <div class="card border-0 bg-light h-100">
              <div class="card-body py-3">
                <h6 class="mb-3"><i class="fa-solid fa-location-dot text-info me-2"></i>Delivery Snapshot</h6>
                <div class="mb-2"><i class="fa-solid fa-user text-muted me-2"></i><span id="checkoutReviewName">-</span></div>
                <div class="mb-2"><i class="fa-solid fa-phone text-muted me-2"></i><span id="checkoutReviewPhone">-</span></div>
                <div><i class="fa-solid fa-map-pin text-muted me-2"></i><span id="checkoutReviewStreet">-</span></div>
              </div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="card border-0 bg-light h-100">
              <div class="card-body py-3">
                <h6 class="mb-3"><i class="fa-solid fa-cart-shopping text-info me-2"></i>Order Snapshot</h6>
                <div class="mb-2"><strong>Items:</strong> <span id="checkoutReviewItems">0</span></div>
                <div class="mb-2"><strong>Quantity:</strong> <span id="checkoutReviewQty">0</span></div>
                <div><strong>Sub Total:</strong> <span id="checkoutReviewTotal">GH&#8373;0.00</span></div>
              </div>
            </div>
          </div>
        </div>

        <div class="table-responsive">
          <table class="table table-striped align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>Product</th>
                <th>Unit Price</th>
                <th>Qty</th>
                <th>Line Total</th>
              </tr>
            </thead>
            <tbody id="checkoutReviewItemsBody">
              <tr>
                <td colspan="4" class="text-center text-muted py-4">Preparing review...</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Back to Edit</button>
        <button type="button" class="btn btn-primary" id="checkoutModalConfirmBtn">Confirm & Continue to Payment</button>
      </div>
    </div>
  </div>
</div>

<script type="module" src="/ecommerce/assets/js/checkout-page.js"></script>
<?php
return ob_get_clean();
?>
