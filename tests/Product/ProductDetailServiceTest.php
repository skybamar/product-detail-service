<?php

declare(strict_types=1);

namespace App\Tests\Product;

use App\Product\Cache\ProductCache;
use App\Product\Counter\ProductRequestCounter;
use App\Product\ProductDetailService;
use App\Product\Repository\ProductRepository;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;

final class ProductDetailServiceTest extends TestCase
{
    #[Test]
    public function cacheMissFetchesFromRepositoryAndCachesResult(): void
    {
        $cache = $this->createInMemoryCache();
        $repository = $this->createRecordingRepository();
        $counter = $this->createRecordingCounter();
        $service = new ProductDetailService($cache, $repository, $counter, $this->createLoggerSpy());

        $result = $service->getDetail('abc-123');

        self::assertSame(['id' => 'abc-123', 'name' => 'From repository'], $result);
        self::assertSame(['abc-123'], $repository->requestedIds);
        self::assertSame($result, $cache->get('abc-123'));
    }

    #[Test]
    public function cacheHitSkipsRepository(): void
    {
        $cache = $this->createInMemoryCache();
        $cache->set('abc-123', ['id' => 'abc-123', 'name' => 'From cache']);
        $repository = $this->createRecordingRepository();
        $service = new ProductDetailService($cache, $repository, $this->createRecordingCounter(), $this->createLoggerSpy());

        $result = $service->getDetail('abc-123');

        self::assertSame(['id' => 'abc-123', 'name' => 'From cache'], $result);
        self::assertSame([], $repository->requestedIds);
    }

    #[Test]
    public function everyRequestIsCountedIncludingCacheHits(): void
    {
        $cache = $this->createInMemoryCache();
        $counter = $this->createRecordingCounter();
        $service = new ProductDetailService($cache, $this->createRecordingRepository(), $counter, $this->createLoggerSpy());

        $service->getDetail('abc-123');
        $service->getDetail('abc-123');

        self::assertSame(['abc-123', 'abc-123'], $counter->incrementedIds);
    }

    #[Test]
    public function cacheWriteFailureIsLoggedAndDoesNotBreakTheResponse(): void
    {
        $cache = new class implements ProductCache {
            public function get(string $id): ?array
            {
                return null;
            }

            public function set(string $id, array $data): void
            {
                throw new \RuntimeException('Disk full.');
            }
        };
        $logger = $this->createLoggerSpy();
        $service = new ProductDetailService($cache, $this->createRecordingRepository(), $this->createRecordingCounter(), $logger);

        $result = $service->getDetail('abc-123');

        self::assertSame(['id' => 'abc-123', 'name' => 'From repository'], $result);
        self::assertCount(1, $logger->records);
    }

    #[Test]
    public function cacheReadFailureIsLoggedAndFallsBackToRepository(): void
    {
        $cache = new class implements ProductCache {
            public function get(string $id): ?array
            {
                throw new \RuntimeException('Cache backend down.');
            }

            public function set(string $id, array $data): void
            {
                throw new \RuntimeException('Cache backend down.');
            }
        };
        $logger = $this->createLoggerSpy();
        $repository = $this->createRecordingRepository();
        $service = new ProductDetailService($cache, $repository, $this->createRecordingCounter(), $logger);

        $result = $service->getDetail('abc-123');

        self::assertSame(['id' => 'abc-123', 'name' => 'From repository'], $result);
        self::assertSame(['abc-123'], $repository->requestedIds);
        self::assertCount(2, $logger->records);
    }

    #[Test]
    public function counterFailureIsLoggedAndDoesNotBreakTheResponse(): void
    {
        $counter = new class implements ProductRequestCounter {
            public function increment(string $id): void
            {
                throw new \RuntimeException('Counter storage unavailable.');
            }
        };
        $logger = $this->createLoggerSpy();
        $service = new ProductDetailService($this->createInMemoryCache(), $this->createRecordingRepository(), $counter, $logger);

        $result = $service->getDetail('abc-123');

        self::assertSame(['id' => 'abc-123', 'name' => 'From repository'], $result);
        self::assertCount(1, $logger->records);
    }

    /**
     * @return ProductCache&object{storage: array<string, array<string, mixed>>}
     */
    private function createInMemoryCache(): ProductCache
    {
        return new class implements ProductCache {
            /** @var array<string, array<string, mixed>> */
            public array $storage = [];

            public function get(string $id): ?array
            {
                return $this->storage[$id] ?? null;
            }

            public function set(string $id, array $data): void
            {
                $this->storage[$id] = $data;
            }
        };
    }

    /**
     * @return ProductRepository&object{requestedIds: list<string>}
     */
    private function createRecordingRepository(): ProductRepository
    {
        return new class implements ProductRepository {
            /** @var list<string> */
            public array $requestedIds = [];

            public function findById(string $id): array
            {
                $this->requestedIds[] = $id;

                return ['id' => $id, 'name' => 'From repository'];
            }
        };
    }

    /**
     * @return ProductRequestCounter&object{incrementedIds: list<string>}
     */
    private function createRecordingCounter(): ProductRequestCounter
    {
        return new class implements ProductRequestCounter {
            /** @var list<string> */
            public array $incrementedIds = [];

            public function increment(string $id): void
            {
                $this->incrementedIds[] = $id;
            }
        };
    }

    /**
     * @return LoggerInterface&object{records: list<array{level: mixed, message: string}>}
     */
    private function createLoggerSpy(): LoggerInterface
    {
        return new class extends AbstractLogger {
            /** @var list<array{level: mixed, message: string}> */
            public array $records = [];

            public function log($level, \Stringable|string $message, array $context = []): void
            {
                $this->records[] = ['level' => $level, 'message' => (string) $message];
            }
        };
    }
}