<?php

declare(strict_types=1);

namespace App\Product\Driver;

/**
 * Contract of the existing driver (given by the assignment). Kept untyped
 * on purpose: native types here would break the drivers that implement it.
 */
interface IElasticSearchDriver
{
    /**
     * @param string $id
     * @return array<string, mixed>
     */
    public function findById($id);
}
