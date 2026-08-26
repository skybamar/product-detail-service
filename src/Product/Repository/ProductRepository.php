<?php

declare(strict_types=1);

namespace App\Product\Repository;

interface ProductRepository
{
    /**
     * @return array<string, mixed>
     */
    public function findById(string $id): array;
}