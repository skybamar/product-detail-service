<?php

declare(strict_types=1);

namespace App\Product\Repository;

final readonly class ProductRepositoryFactory
{
    public function __construct(
        private ElasticSearchProductRepository $elasticSearch,
        private MySqlProductRepository $mySql,
    ) {
    }

    public function create(string $source): ProductRepository
    {
        return match ($source) {
            'elasticsearch' => $this->elasticSearch,
            'mysql' => $this->mySql,
            default => throw new \InvalidArgumentException(sprintf(
                'Unknown product source "%s", expected "elasticsearch" or "mysql".',
                $source,
            )),
        };
    }
}