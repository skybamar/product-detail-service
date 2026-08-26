<?php

declare(strict_types=1);

namespace App\Tests\Product\Repository;

use App\Product\Driver\InMemoryElasticSearchDriver;
use App\Product\Driver\InMemoryMySQLDriver;
use App\Product\Repository\ElasticSearchProductRepository;
use App\Product\Repository\MySqlProductRepository;
use App\Product\Repository\ProductRepositoryFactory;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ProductRepositoryFactoryTest extends TestCase
{
    private ProductRepositoryFactory $factory;

    protected function setUp(): void
    {
        $this->factory = new ProductRepositoryFactory(
            new ElasticSearchProductRepository(new InMemoryElasticSearchDriver()),
            new MySqlProductRepository(new InMemoryMySQLDriver()),
        );
    }

    #[Test]
    public function createsElasticSearchRepository(): void
    {
        self::assertInstanceOf(ElasticSearchProductRepository::class, $this->factory->create('elasticsearch'));
    }

    #[Test]
    public function createsMySqlRepository(): void
    {
        self::assertInstanceOf(MySqlProductRepository::class, $this->factory->create('mysql'));
    }

    #[Test]
    public function rejectsUnknownSource(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown product source "postgres"');

        $this->factory->create('postgres');
    }
}