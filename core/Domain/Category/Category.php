<?php
namespace Domain\Category;

use DateTimeImmutable;

class Category
{
    private ?int $id;
    private string $name;
    private string $slug;
    private ?int $parent_id;
    private string $status;
    private DateTimeImmutable $createdAt;
    private DateTimeImmutable $updatedAt;

    private function __construct(
        ?int $id,
        string $name,
        string $slug,
        ?int $parent_id,
        string $status,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->slug = $slug;
        $this->parent_id = $parent_id;
        $this->status = $status;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }

    public static function create(
        string $name,
        string $slug,
        ?int $parent_id = null,
        string $status = 'active'
    ): self {
        if (trim($name) === '') {
            throw new \InvalidArgumentException('Category name cannot be empty');
        }

        return new self(
            null,
            $name,
            $slug,
            $parent_id,
            $status,
            new DateTimeImmutable(),
            new DateTimeImmutable()
        );
    }

    public static function fromPersistence(
        int $id,
        string $name,
        string $slug,
        ?int $parent_id,
        string $status,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt
    ): self {
        return new self(
            $id,
            $name,
            $slug,
            $parent_id,
            $status,
            $createdAt,
            $updatedAt
        );
    }

    public function getId(): ?int { return $this->id; }
    public function getName(): string { return $this->name; }
    public function getSlug(): string { return $this->slug; }
    public function getParentId(): ?int { return $this->parent_id; }
    public function getStatus(): string { return $this->status; }
    public function getCreatedAt(): DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): DateTimeImmutable { return $this->updatedAt; }

    public function setId(int $id): void
    {
        if ($this->id !== null) {
            throw new \LogicException('Category ID is already set');
        }

        $this->id = $id;
    }

    public function updateDetails(
        string $name,
        string $slug,
        ?int $parent_id,
        string $status
    ): void {
        if (trim($name) === '') {
            throw new \InvalidArgumentException('Category name cannot be empty');
        }

        $this->name = $name;
        $this->slug = $slug;
        $this->parent_id = $parent_id;
        $this->status = $status;
        $this->touch();
    }

    private function touch(): void
    {
        $this->updatedAt = new DateTimeImmutable();
    }
}
