<?php

declare(strict_types=1);

namespace App\Controller;

use App\Product\ProductDetailService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
final readonly class ProductController
{
    public function __construct(
        private ProductDetailService $productDetailService,
    ) {
    }

    #[Route('/product/{id}', name: 'product_detail', methods: ['GET'])]
    public function detail(string $id): JsonResponse
    {
        return new JsonResponse($this->productDetailService->getDetail($id));
    }
}