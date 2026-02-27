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
5) Checkout button routes user to dedicated checkout page.
-->
<div class="container my-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="fa-solid fa-cart-shopping text-info me-2"></i>My Cart</h4>
    <a href="/ecommerce/index.php?page=home" class="btn btn-sm btn-outline-secondary">Continue Shopping</a>
  </div>

  <div class="row g-4">
    <div class="col-lg-8">
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
    </div>

    <div class="col-lg-4">
      <div class="card shadow-sm border-0">
        <div class="card-body" id="cartSummary">
          <div class="row g-2 mb-3">
            <div class="col-6">
              <div class="p-3 rounded border bg-light h-100">
                <div class="small text-muted"><i class="fa-solid fa-box me-1 text-info"></i>Total Items</div>
                <div class="fs-5 fw-bold">0</div>
              </div>
            </div>
            <div class="col-6">
              <div class="p-3 rounded border bg-light h-100">
                <div class="small text-muted"><i class="fa-solid fa-layer-group me-1 text-info"></i>Quantity</div>
                <div class="fs-5 fw-bold">0</div>
              </div>
            </div>
          </div>

          <div class="p-3 rounded border bg-light mb-3 d-flex justify-content-between align-items-center">
            <span class="text-muted"><i class="fa-solid fa-coins me-1 text-warning"></i>Sub Total</span>
            <strong class="text-dark fs-5">GH&#8373;0.00</strong>
          </div>

          <div class="d-flex justify-content-center">
            <button class="btn btn-primary px-4" id="cartCheckoutBtn" type="button" disabled>
              <i class="fa-solid fa-credit-card me-2"></i>Checkout (GH&#8373;0.00)
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script type="module" src="/ecommerce/assets/js/cart-page.js"></script>
<?php
return ob_get_clean();
?>
