<!-- Footer -->
  <footer class="bg-info text-center text-white py-2 mt-3">
    <p class="mb-0">&copy; 2025 YourCompanyName. All rights reserved.</p>
  </footer>

  <!-- Login Modal Overlay -->
<div id="loginModalOverlay" style="display:none; position:fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(0,0,0,0.5); z-index:2000; align-items:center; justify-content:center;">
  <div id="loginModalInner"></div>
</div>

  <!-- Storefront JS -->
  <!--
  Global storefront controller (assets/js/script.js) responsibilities:
  1) Loads auth state via /shared/api/auth/me.php
  2) Handles login/register modal submissions
  3) Handles logout
  4) Handles add-to-cart clicks from product cards
  5) Keeps cart badge in sync
  -->
  <script type="module" src="/ecommerce/assets/js/script.js"></script>

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
