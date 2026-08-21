<?php

declare(strict_types=1);

namespace App\Product\Driver;

final class InMemoryElasticSearchDriver implements IElasticSearchDriver
{
    public function findById($id)
    {
        return [
            'id' => $id,
            'name' => sprintf('Product %s', $id),
            'price' => 199.0,
            'source' => 'elasticsearch',
        ];
    }
}