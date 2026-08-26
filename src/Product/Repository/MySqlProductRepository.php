<?php

declare(strict_types=1);

namespace App\Product\Repository;

use App\Product\Driver\IMySQLDriver;

final readonly class MySqlProductRepository implements ProductRepository
{
    public function __construct(
        private IMySQLDriver $driver,
    ) {
    }

    public function findById(string $id): array
    {
        return $this->driver->findProduct($id);
    }
}