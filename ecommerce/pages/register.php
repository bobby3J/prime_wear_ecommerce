<!--
Register modal fragment.
Rendered dynamically by assets/js/script.js into #loginModalInner.
Submit is intercepted in script.js and sent to /shared/api/auth/register.php.
-->
<div class="login-modal-content card shadow-lg p-4">
  <button type="button" class="btn-close position-absolute top-0 end-0 m-3" id="registerCloseBtn" aria-label="Close"></button>

  <div class="text-center mb-4">
    <img src="./assets/images/crown1.png" alt="logo" style="height: 48px;">
    <h3 class="mt-2 text-gold fw-bold">Sign Up</h3>
  </div>

  <form id="registerForm">
    <div class="mb-3">
      <label for="reg_name" class="form-label">Full Name</label>
      <input type="text" id="reg_name" name="name" class="form-control" placeholder="Enter your name" required>
    </div>

    <div class="mb-3">
      <label for="reg_email" class="form-label">Email</label>
      <input type="email" id="reg_email" name="email" class="form-control" placeholder="Enter your email" required>
    </div>

    <div class="mb-3">
      <label for="reg_password" class="form-label">Password</label>
      <input type="password" id="reg_password" name="password" class="form-control" placeholder="Create a password" minlength="6" required>
    </div>
    
    <div class="mb-3">
        <label for="reg_password_confirm" class="form-label">Confirm Password</label>
        <input type="password" id="reg_password_confirm" name="password_confirm" class="form-control" placeholder="Confirm password" minlength="6" required>
    </div>
    <div data-auth-error></div>

    <button type="submit" class="btn btn-gold w-50 fw-bold mx-auto d-block">Create Account</button>
  </form>

  <div class="text-center mt-3">
    <span class="text-muted">Already have an account?</span>
    <a href="#" id="switchToLogin" class="text-gold fw-semibold">Sign In</a>
  </div>
</div>

<style>
  .btn-gold {
    background-color: #C8A951;
    color: #222;
    border: none;
    padding: 0.45rem 1rem;
    font-size: 0.95rem;
  }
  .btn-gold:hover {
    background-color: #a18945ff;
    color: #111010ff;
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
  .login-modal-content .form-control {
    color: #000 !important;
    background-color: #fff !important;
  }

.login-modal-content .form-control::placeholder {
    color: #bbb !important;
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
