<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;

final class ProductControllerTest extends KernelTestCase
{
    #[Test]
    public function endpointReturnsProductJson(): void
    {
        $kernel = self::bootKernel();

        $response = $kernel->handle(Request::create('/product/it-42'));

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('application/json', $response->headers->get('Content-Type'));
        $data = json_decode((string) $response->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        self::assertIsArray($data);
        self::assertSame('it-42', $data['id'] ?? null);
    }
}
