<?php
// router.php
$page = $_GET['page'] ?? 'index';
$file = "pages/$page.php";

if (file_exists($file)){
    include($file);
} else {
    ob_start();
    ?>
    <div class="alert alert-danger text-center mt-5">
        <h4>Page Not Found</h4>
        <p>The page you're looking for doesn't exist.</p>
        <a href="?page=index" class="btn btn-primary">Go Home</a>
    </div>
    <?php
    $content = ob_get_clean();
    include('layout/main_layout.php');
}
?>
