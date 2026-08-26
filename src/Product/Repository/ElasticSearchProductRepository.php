<?php

declare(strict_types=1);

namespace App\Product\Repository;

use App\Product\Driver\IElasticSearchDriver;

final readonly class ElasticSearchProductRepository implements ProductRepository
{
    public function __construct(
        private IElasticSearchDriver $driver,
    ) {
    }

    public function findById(string $id): array
    {
        return $this->driver->findById($id);
    }
}