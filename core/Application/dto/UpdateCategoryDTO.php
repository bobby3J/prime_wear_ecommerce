<?php
namespace Application\DTO;

class UpdateCategoryDTO
{
    public function __construct(
        public int $id,
        public string $name,
        public string $status
    ) {}
}
