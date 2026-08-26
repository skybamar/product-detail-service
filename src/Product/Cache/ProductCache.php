<?php

declare(strict_types=1);

namespace App\Product\Cache;

interface ProductCache
{
    /**
     * @return array<string, mixed>|null
     */
    public function get(string $id): ?array;

    /**
     * @param array<string, mixed> $data
     */
    public function set(string $id, array $data): void;
}