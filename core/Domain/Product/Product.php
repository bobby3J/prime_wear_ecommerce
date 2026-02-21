<?php
namespace Domain\Product;

use DateTimeImmutable;
use Domain\Product\ProductImage;


class Product {
    private ?int $id;
    private int $category_id;
    private string $name;
    private string $slug;
    private ?string $description;
    private float $price;
    private int $stock;
    private string $status;
    private DateTimeImmutable $createdAt;
    private DateTimeImmutable $updatedAt;

    /** @var ProductImage[] */
    private array $images = [];

    private function __construct(
        ?int $id,
        int $category_id,
        string $name,
        string $slug,
        ?string $description,
        float $price,
        int $stock,
        string $status,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt
    )
    {
        $this->id = $id;
        $this->category_id = $category_id;
        $this->name = $name;
        $this->slug = $slug;
        $this->description = $description;
        $this->price = $price;
        $this->stock = $stock;
        $this->status = $status;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }
    /**
     * Factory for creating a new product
     */
    public static function create(
        int $category_id,
        string $name,
        string $slug,
        ?string $description,
        float $price,
        int $stock
    ): self {
        if ($price <= 0) throw new \InvalidArgumentException('Price must be greater than zero');

        if ($stock < 0) throw new \InvalidArgumentException('Stock cannot be negative');

        if (trim($name) === '') throw new \InvalidArgumentException('Name cannot be empty');

        return new self (
            null,
            $category_id,
            $name,
            $slug,
            $description,
            $price,
            $stock,
            'active',
            new DateTimeImmutable(),
            new DateTimeImmutable()
        );
    }

    /**
     * Reconstitute from persistence (DB)
     */
    public static function fromPersistence(
        int $id,
        int $category_id,
        string $name,
        string $slug,
        ?string $description,
        float $price,
        int $stock,
        string $status,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt
    ): self {
        return new self (
            $id,
            $category_id,
            $name,
            $slug,
            $description,
            $price,
            $stock,
            $status,
            $createdAt,
            $updatedAt
        );
    }

    // getters only (immutability mindset)

    public function getId(): ?int { return $this->id; }

    public function getCategoryId(): int { return $this->category_id; }

    public function getName(): string { return $this->name; }

    public function getSlug(): string { return $this->slug; }

    public function getDescription(): ?string { return $this->description; }

    public function getPrice(): float { return $this->price; }

    public function getStock(): int { return $this->stock; }
     
    public function getStatus(): string { return $this->status; }

    public function getImages(): array { return $this->images; }

    public function getCreatedAt(): DateTimeImmutable { return $this->createdAt; }

    public function getUpdatedAt(): DateTimeImmutable { return $this->updatedAt; }

    // Allow infrastructure to set ID once

    public function setId(int $id): void
    {
        if ($this->id !== null) {
            throw new \LogicException('Product ID is already set');
        }

        $this->id = $id;
    }

    public function updateDetails(
        int $category_id,
        string $name,
        string $slug,
        ?string $description,
        float $price,
        int $stock
    ): void {
        if ($price <= 0) throw new \InvalidArgumentException('Price must be greater than zero');
        if ($stock < 0) throw new \InvalidArgumentException('Stock cannot be negative');
        if (trim($name) === '') throw new \InvalidArgumentException('Name cannot be empty');

        $this->category_id = $category_id;
        $this->name = $name;
        $this->slug = $slug;
        $this->description = $description;
        $this->price = $price;
        $this->stock = $stock;
        $this->touch();
    }
    
    // business behavior

    public function markOutOfStock(): void 
    {
        if ($this->stock > 0) throw new \DomainException('Cannot mark product as out of stock when stock is available');
        $this->status = 'out_of_stock';
    }

    public function addImage(ProductImage $image): void
    {
        $this->images[] = $image;
    }

    private function touch(): void 
    {
        $this->updatedAt = new DateTimeImmutable();
    }

    public function changePrice(float $newPrice): void
    {
        if ($newPrice <= 0) {
            throw new \DomainException('Price must be greater than zero');
        }

        $this->price = $newPrice;
        $this->touch();
    }

    public function decreaseStock(int $qty): void
    {
        if ($qty <= 0) {
            throw new \DomainException('Quantity must be positive');
        }

        if ($qty > $this->stock) {
            throw new \DomainException('Insufficient stock');
        }

        $this->stock -= $qty;

        if ($this->stock === 0) {
            $this->status = 'out_of_stock';
        }

        $this->touch();
    }

    public function publish(): void
    {
        if ($this->stock === 0) {
            throw new \DomainException('Cannot publish product with zero stock');
        }
        $this->status = 'active';
        $this->touch();
    }

    public function draft(): void
    {
        $this->status = 'draft';
        $this->touch();
    }
    
}
