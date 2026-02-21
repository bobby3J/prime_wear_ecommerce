<?php
// Storefront entry point.
// Flow:
// 1) Web request lands here (e.g. /ecommerce/index.php?page=home).
// 2) This file delegates rendering to layout/main.php.
// 3) layout/main.php resolves the requested page and includes it.
include __DIR__ . '/layout/main.php';
?>
