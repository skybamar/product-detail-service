<?php

declare(strict_types=1);

namespace App\Product\Driver;

final class InMemoryMySQLDriver implements IMySQLDriver
{
    public function findProduct($id)
    {
        return [
            'id' => $id,
            'name' => sprintf('Product %s', $id),
            'price' => 199.0,
            'source' => 'mysql',
        ];
    }
}