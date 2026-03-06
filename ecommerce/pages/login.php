<!--
Login modal fragment.
Rendered dynamically by assets/js/script.js into #loginModalInner.
Submit is intercepted in script.js and sent to /shared/api/auth/login.php.
-->
<div class="login-modal-content card shadow-lg p-4">
  <button type="button" class="btn-close position-absolute top-0 end-0 m-3" id="loginCloseBtn" aria-label="Close"></button>
  <div class="text-center mb-4">
    <img src="./assets/images/crown1.png" alt="logo" style="height: 48px;">
    <h3 class="mt-2 text-gold fw-bold">Sign In</h3>
  </div>
  <form id="loginForm">
    <div class="mb-3">
      <label for="email" class="form-label">Email address</label>
      <input type="email" class="form-control" id="email" name="email" placeholder="Enter your email" required>
    </div>
    <div class="mb-3">
      <label for="password" class="form-label">Password</label>
      <input type="password" class="form-control" id="password" name="password" placeholder="Enter your password" required>
    </div>
    <div data-auth-error></div>
    <button type="submit" class="btn btn-gold w-50 fw-bold mx-auto d-block">Sign In</button>
  </form>
  <div class="text-center mt-3">
    <span class="text-muted">Don't have an account?</span>
    <a href="#" id="switchToRegister" class="text-gold fw-semibold">Sign Up</a>
  </div>
</div>
<style>
  .btn-gold {
    background-color: #C8A951;
    color: #fff;
    border: none;
    padding: 0.45rem 1rem;
    font-size: 0.95rem;
  }
  .btn-gold:hover {
    background-color: #a18945ff;
    color: #fff;
  }
  .text-gold {
    color: #FFD700;
  }
  .login-modal-content {
    max-width: 480px;
    width: 90vw;
    min-width: 300px;
    position: relative;
    transition: transform 0.3s cubic-bezier(.4,0,.2,1), opacity 0.3s cubic-bezier(.4,0,.2,1);
    transform: scale(0.95);
    opacity: 0;
  }
  #loginModalOverlay[style*="display: flex"] .login-modal-content {
    transform: scale(1);
    opacity: 1;
  }
  @media (max-width: 576px) {
    .login-modal-content {
      max-width: 98vw;
      padding: 1.5rem 0.5rem;
    }
  }
</style>

