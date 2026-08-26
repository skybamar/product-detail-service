<?php

declare(strict_types=1);

namespace App\Tests\Product\Repository;

use App\Product\Driver\IMySQLDriver;
use App\Product\Repository\MySqlProductRepository;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class MySqlProductRepositoryTest extends TestCase
{
    #[Test]
    public function passesIdToDriverAndReturnsItsDataUnchanged(): void
    {
        $driver = new class implements IMySQLDriver {
            public ?string $receivedId = null;

            public function findProduct($id)
            {
                $this->receivedId = $id;

                return ['id' => $id, 'name' => 'Test product'];
            }
        };

        $repository = new MySqlProductRepository($driver);

        $result = $repository->findById('abc-123');

        self::assertSame('abc-123', $driver->receivedId);
        self::assertSame(['id' => 'abc-123', 'name' => 'Test product'], $result);
    }
}