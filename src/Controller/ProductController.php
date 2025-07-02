<?php

namespace App\Controller;

use App\Entity\Product;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Attributes as OA;
use Nelmio\ApiDocBundle\Attribute\Model;

#[Route('/api/products', name: 'api_products_')]
#[OA\Tag(name: 'Products')]
class ProductController extends AbstractController
{
    public function __construct(
        private ProductRepository $productRepository
    ) {}

    #[Route('', name: 'list', methods: ['GET'])]
    #[OA\Get(
        path: '/api/products',
        description: 'Récupère la liste des téléphones mobiles',
        summary: 'Liste des produits',
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Liste des produits',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(ref: new Model(type: Product::class, groups: ['product:list']))
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Non authentifié',
                content: new OA\JsonContent(
                    properties: [
                        'error' => new OA\Property(property: 'error', type: 'string', example: 'JWT Token not found')
                    ]
                )
            )
        ]
    )]
    public function list(): JsonResponse
    {
        $products = $this->productRepository->findAll();
        return $this->json($products, 200, [], ['groups' => ['product:list']]);
    }

    #[Route('/{id}', name: 'show', requirements: ['id' => '\d+'], methods: ['GET'])]
    #[OA\Get(
        path: '/api/products/{id}',
        description: 'Récupère les informations détaillées d\'un téléphone mobile spécifique',
        summary: 'Détail d\'un produit',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'Identifiant unique du produit',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', minimum: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Détails complets du produit',
                content: new OA\JsonContent(ref: new Model(type: Product::class, groups: ['product:read']))
            ),
            new OA\Response(
                response: 404,
                description: 'Produit non trouvé',
                content: new OA\JsonContent(
                    properties: [
                        'error' => new OA\Property(property: 'error', type: 'string', example: 'Product not found')
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Non authentifié',
                content: new OA\JsonContent(
                    properties: [
                        'error' => new OA\Property(property: 'error', type: 'string', example: 'JWT Token not found')
                    ]
                )
            )
        ]
    )]
    public function show(int $id): JsonResponse
    {
        $product = $this->productRepository->find($id);

        if (!$product) {
            return $this->json(['error' => 'Product not found'], 404);
        }

        return $this->json($product, 200, [], ['groups' => ['product:read']]);
    }
}
