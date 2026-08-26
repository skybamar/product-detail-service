<?php

declare(strict_types=1);

namespace App\Tests\Product\Repository;

use App\Product\Driver\IElasticSearchDriver;
use App\Product\Repository\ElasticSearchProductRepository;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ElasticSearchProductRepositoryTest extends TestCase
{
    #[Test]
    public function passesIdToDriverAndReturnsItsDataUnchanged(): void
    {
        $driver = new class implements IElasticSearchDriver {
            public ?string $receivedId = null;

            public function findById($id)
            {
                $this->receivedId = $id;

                return ['id' => $id, 'name' => 'Test product'];
            }
        };

        $repository = new ElasticSearchProductRepository($driver);

        $result = $repository->findById('abc-123');

        self::assertSame('abc-123', $driver->receivedId);
        self::assertSame(['id' => 'abc-123', 'name' => 'Test product'], $result);
    }
}