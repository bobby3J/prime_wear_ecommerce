<!-- Footer -->
  <footer class="footer-gold text-center text-white py-2 mt-3">
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

  <script>
    (function () {
      const applyStorefrontMinHeight = function () {
        const main = document.querySelector('.storefront-main');
        if (!main) return;

        const topbar = document.querySelector('.topbar');
        const navbar = document.querySelector('.navbar');
        const footer = document.querySelector('footer');
        const mainStyles = window.getComputedStyle(main);

        const topbarHeight = topbar ? topbar.offsetHeight : 0;
        const navbarHeight = navbar ? navbar.offsetHeight : 0;
        const footerHeight = footer ? footer.offsetHeight : 0;
        const verticalSpacing =
          parseFloat(mainStyles.marginTop || '0') +
          parseFloat(mainStyles.marginBottom || '0');

        const minHeight = Math.max(
          0,
          window.innerHeight - topbarHeight - navbarHeight - footerHeight - verticalSpacing
        );

        main.style.minHeight = minHeight + 'px';
      };

      window.addEventListener('load', applyStorefrontMinHeight);
      window.addEventListener('resize', applyStorefrontMinHeight);
      document.addEventListener('DOMContentLoaded', applyStorefrontMinHeight);
    })();
  </script>

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
