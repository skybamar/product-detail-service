<?php

declare(strict_types=1);

namespace App\Product;

use App\Product\Cache\ProductCache;
use App\Product\Counter\ProductRequestCounter;
use App\Product\Repository\ProductRepository;
use Psr\Log\LoggerInterface;

final readonly class ProductDetailService
{
    public function __construct(
        private ProductCache $cache,
        private ProductRepository $repository,
        private ProductRequestCounter $counter,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getDetail(string $id): array
    {
        try {
            $product = $this->cache->get($id);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to read cached product data.', ['id' => $id, 'exception' => $e]);
            $product = null;
        }

        if ($product === null) {
            $product = $this->repository->findById($id);

            try {
                $this->cache->set($id, $product);
            } catch (\Throwable $e) {
                $this->logger->error('Failed to cache product data.', ['id' => $id, 'exception' => $e]);
            }
        }

        try {
            $this->counter->increment($id);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to count product request.', ['id' => $id, 'exception' => $e]);
        }

        return $product;
    }
}