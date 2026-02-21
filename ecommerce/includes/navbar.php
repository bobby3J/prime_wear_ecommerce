<!-- Top Info Bar -->
<div class="topbar d-flex justify-content-between align-items-center px-4 py-2 bg-white shadow-sm">
  <!-- Left: Contact -->
  <div class="contact-info d-flex align-items-center d-none d-md-flex">
    <i class="fa-solid fa-phone text-gold me-2 fs-5"></i>
    <span class="text-dark fw-semibold">053 385 5022</span>
  </div>
  <!-- Brand + Slogan -->
  <div class="brand-info text-center mx-auto">
    <img src="./assets/images/crown1.png" alt="logo" class="logo" style="height: 40px;">
    <strong class="text-gold fs-5">Prime Wear</strong>
    <span class="text-muted slogan"> | Your Prime Choice for Comfort</span>
  </div>
  <!-- Right Side: Location + Payments -->
  <div class="topbar-right d-flex align-items-center">
    <!-- Location -->
    <div class="me-4 d-flex align-items-center">
      <i class="fa-solid fa-location-dot text-gold me-1 fs-5"></i>
      <span class="text-dark fw-semibold">Darkuman</span>
    </div>
    <!-- Payment Icons -->
    <div class="payment-icons d-flex align-items-center justify-content-center">
      <img src="./assets/images/mtn1.jpg" alt="MTN MoMo" class="payment-icon me-3">
      <img src="./assets/images/credit2.png" alt="Card Payment" class="payment-icon me-3">
      <img src="./assets/images/cod2.png" alt="Cash on Delivery" class="payment-icon">
    </div>
  </div>
</div>

<!-- Navbar -->
<!--
Navigation flow notes:
- Cart badge (#cartCountBadge) is updated by assets/js/script.js using /shared/api/cart/count.php.
- Profile icon click is handled by assets/js/script.js:
  - If logged out => opens login/register modal.
  - If logged in => logs out via /shared/api/auth/logout.php.
- Search submits to index.php?page=home&q=...
  home.php + product-fetch.js then calls /shared/api/products.php using q filter.
-->
<nav class="navbar navbar-expand-lg navbar-light bg-gold shadow-sm sticky-top" style="z-index: 1030;">
  <div class="container-fluid">
    <!-- Left: Logo -->
    <a class="navbar-brand d-flex align-items-center" href="#">
      <img src="./assets/images/crown1.png" alt="logo" class="logo" style="height: 40px;">
    </a>
    <!-- Navbar toggler (mobile view) -->
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent">
      <span class="navbar-toggler-icon"></span>
    </button>
    <!-- Cart and Profile: always visible on right -->
    <div class="d-flex align-items-center order-lg-2 ms-auto">
      <a class="nav-link position-relative me-3" href="/ecommerce/index.php?page=cart">
        <i class="fa-solid fa-cart-shopping fa-lg"></i>
        <span id="cartCountBadge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">0</span>
      </a>
      <a class="nav-link profile-trigger" href="javascript:void(0)">
        <i class="fa-solid fa-user fa-lg"></i>
      </a>
    </div>
    <!-- Navbar content -->
    <div class="collapse navbar-collapse order-lg-1" id="navbarSupportedContent">

      <!-- Left links -->
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item"><a class="nav-link active" href="/ecommerce/index.php?page=home">Home</a></li>
        <li class="nav-item"><a class="nav-link" href="/ecommerce/index.php?page=contact">Contact</a></li>
        <li class="nav-item"><a class="nav-link" href="/ecommerce/index.php?page=cart">Cart</a></li>
      </ul>

      <!-- Center: Search bar -->
      <form class="d-flex mx-auto" role="search" style="width: 40%;" method="get" action="/ecommerce/index.php">
        <input type="hidden" name="page" value="home">
        <input class="form-control me-2" type="search" name="q" placeholder="Search products" aria-label="Search">
        <button class="btn btn-outline-light" type="submit">Search</button>
      </form>

    </div>
  </div>
</nav>

