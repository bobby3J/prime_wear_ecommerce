<?php
ob_start();
?>
<div class="container text-center mt-5">
  <h1 class="display-4 text-danger">404</h1>
  <p class="lead">Oops! The page you’re looking for doesn’t exist.</p>
  <a href="index.php?page=home" class="btn btn-info mt-3">Back to Home</a>
</div>
<?php
return ob_get_clean();
?>
