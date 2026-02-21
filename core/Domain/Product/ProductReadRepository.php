<?php
namespace Domain\Product;

interface ProductReadRepository {
    public function fetchAll(): array;
}
