<?php
namespace Domain\Customer;

interface CustomerRepository
{
    public function findById(int $id): ?Customer;

    public function findByEmail(string $email): ?Customer;

    public function create(string $name, string $email, string $passwordHash): Customer;
}
