<?php

require __DIR__ . '/../bootstrap.php';

use Application\DTO\CreateProductDTO;
use Application\Usecases\CreateProductUseCase;
use Infrastructure\Persistence\MySQLProductRepository;
use Core\Infrastructure\Persistence\Database;

// Get PDO
$pdo = Database::getConnection();


$productRepository = new MySQLProductRepository($pdo);

// Use case 
$useCase = new CreateProductUseCase($productRepository);

// Create DTO and populate with test data
$dto = new CreateProductDTO(
    category_id: 2,
    name: 'Australian Panties',
    description: 'Latest fashion panties for women',
    price: 1999.99,
    stock: 15
);

// execute
$product = $useCase->execute($dto);

// cli feedback
echo "Product created:\n";
echo "ID: " . $product->getId() . "\n";
echo "Name: " . $product->getName() . "\n"; 
echo "Slug: " . $product->getSlug() . "\n";
echo "Price: $" . $product->getPrice() . "\n";
echo "Stock: " . $product->getStock() . "\n";


