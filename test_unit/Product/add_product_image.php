<?php
require __DIR__ . '/../bootstrap.php';

use Core\Infrastructure\Persistence\Database;
use Infrastructure\Persistence\MySQLProductImageRepository;
use Application\Usecases\AddProductImageUseCase;

// fake product id for testing
$productId = 2;

// fake image path (simulate uploader output)
$path = 'uploads/products/sneaker1.jpg';

$pdo = Database::getConnection();
$imageRepo = new MySQLProductImageRepository($pdo);
$useCase = new AddProductImageUseCase($imageRepo);

$useCase->execute(
    $productId,
    $path,
    true
);

echo "Image added to product ID $productId\n";