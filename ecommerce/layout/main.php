<?php include __DIR__ . '/../includes/header.php'; ?>
<?php include __DIR__ . '/../includes/navbar.php'; ?>

<div class="container-fluid my-4">
  <div class="row">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <div class="col-md-10">
      <?php 
      // Page resolution flow:
      // - Reads ?page=... from query string.
      // - Sanitizes value to avoid including arbitrary file paths.
      // - Includes matching file from ecommerce/pages.
      // - Falls back to pages/404.php when no match exists.
      //
      // Connection to the rest of the app:
      // - pages/home.php renders products grid and loads product-fetch.js.
      // - pages/cart.php renders cart table and loads cart-page.js.
      // - Global storefront behavior (login/cart badge/add-to-cart) is loaded
      //   from includes/footer.php via assets/js/script.js.
      $page = $_GET['page'] ?? 'home';
      $safePage = preg_replace('/[^a-zA-Z0-9_-]/', '', $page);
      $pageFile = __DIR__ . '/../pages/' . $safePage . '.php';
      if (is_file($pageFile)) {
        $pageResult = include $pageFile;
        if (is_string($pageResult)) {
          echo $pageResult;
        }
      } else {
        echo include __DIR__ . '/../pages/404.php';
      }
      ?>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
