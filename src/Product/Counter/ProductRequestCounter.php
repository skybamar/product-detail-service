<?php

declare(strict_types=1);

namespace App\Product\Counter;

interface ProductRequestCounter
{
    public function increment(string $id): void;
}