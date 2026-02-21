<?php
ob_start();
?>
<!--
Home page flow:
1) Renders empty .products-row container.
2) Loads product-fetch.js.
3) product-fetch.js requests /shared/api/products.php and injects cards.
4) Product cards include data-add-to-cart button.
5) Global script.js captures add-to-cart clicks and calls /shared/api/cart/add.php.
-->

<div class="container my-4">
  <div class="row g-4 justify-content-center products-row"><!-- JS injects product cards here --></div>
</div>

<script type="module" src="/ecommerce/assets/js/product-fetch.js"></script>
<?php
return ob_get_clean();
?>
