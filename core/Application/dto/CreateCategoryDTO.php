<?php
namespace Application\DTO;

class CreateCategoryDTO
{
    public function __construct(
        public string $name,
        public string $status,
        public ?int $parentId = null
    ) {}
}
