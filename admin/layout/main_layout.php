
<?php 
if (!isset($content)) {
  die('main_layout.php loaded without content');
}
?>
<?php include(__DIR__ . '/../includes/header.php'); ?>
<?php include(__DIR__ . '/../includes/navbar.php'); ?>

<main>
  <?php include(__DIR__ . '/../includes/sidebar.php'); ?>

  <div class="main-column">
    <div class="content">
      <?php echo $content ?? ''; ?>
    </div>
    <?php include(__DIR__ . '/../includes/footer.php'); ?>
  </div>
</main>
