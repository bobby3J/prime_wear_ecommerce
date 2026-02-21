<?php
namespace Domain\Product;

class ProductImage {
    private ?int $id;
    private string $path;
    private bool $isPrimary;

    private function __construct(?int $id, string $path, bool $isPrimary)
    {
        $this->id = $id;
        $this->path = $path;
        $this->isPrimary = $isPrimary;
    }

    public static function create(string $path, bool $isPrimary = false): self
    {
        return new self(null, $path, $isPrimary);
    }

    public static function fromPersistence(int $id, string $path, bool $isPrimary): self
    {
        return new self($id, $path, $isPrimary);
    }

    public function path(): string
    {
        return $this->path;
    }

    public function isPrimary(): bool
    {
        return $this->isPrimary;
    }
}