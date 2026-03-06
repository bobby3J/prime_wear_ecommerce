<?php
if (!isset($content)) {
    die('auth_layout.php loaded without content');
}
?>
<?php include __DIR__ . '/../includes/header.php'; ?>

<div class="container-fluid py-4" style="min-height: 100vh; display: flex; align-items: center; justify-content: center;">
  <div style="width: min(96vw, 980px);">
    <?= $content ?>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>