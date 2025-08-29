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
        description: 'Liste des téléphones mobiles',
        summary: 'Liste des produits',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'page',
                description: 'Numéro de page',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
            new OA\Parameter(
                name: 'limit',
                description: 'Éléments par page',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', example: 20)
            ),
            new OA\Parameter(
                name: 'brand',
                description: 'Filtrer par marque',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', example: 'Apple')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Liste des produits'
            ),
            new OA\Response(
                response: 401,
                description: 'Non authentifié'
            )
        ]
    )]
    public function list(Request $request): JsonResponse
    {
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = min(100, max(1, (int) $request->query->get('limit', 20)));
        $brand = $request->query->get('brand');

        $queryBuilder = $this->productRepository->createQueryBuilder('p');

        if ($brand) {
            $queryBuilder
                ->where('p.brand LIKE :brand')
                ->setParameter('brand', '%' . $brand . '%');
        }

        $totalQuery = clone $queryBuilder;
        $totalItems = (int) $totalQuery
            ->select('COUNT(p.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $products = $queryBuilder
            ->select('p')
            ->orderBy('p.createdAt', 'DESC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        $totalPages = (int) ceil($totalItems / $limit);

        $baseUrl = $request->getSchemeAndHttpHost() . $request->getPathInfo();
        $brandParam = $brand ? '&brand=' . urlencode($brand) : '';

        $links = [
            'self' => $baseUrl . '?page=' . $page . $brandParam,
            'first' => $baseUrl . '?page=1' . $brandParam,
            'last' => $baseUrl . '?page=' . $totalPages . $brandParam,
        ];

        if ($page > 1) {
            $links['prev'] = $baseUrl . '?page=' . ($page - 1) . $brandParam;
        }

        if ($page < $totalPages) {
            $links['next'] = $baseUrl . '?page=' . ($page + 1) . $brandParam;
        }

        $productsWithLinks = array_map(function($product) use ($request) {
            return [
                ...$this->normalizeProduct($product),
                '_links' => [
                    'self' => $request->getSchemeAndHttpHost() . '/api/products/' . $product->getId()
                ]
            ];
        }, $products);

        $response = new JsonResponse([
            'data' => $productsWithLinks,
            'meta' => [
                'current_page' => $page,
                'total_pages' => $totalPages,
                'total_items' => $totalItems,
                'items_per_page' => $limit
            ],
            '_links' => $links
        ]);

        $response->setMaxAge(3600);
        $response->headers->set('Cache-Control', 'public, max-age=3600');

        return $response;
    }

    #[Route('/{id}', name: 'show', requirements: ['id' => '\d+'], methods: ['GET'])]
    #[OA\Get(
        path: '/api/products/{id}',
        description: 'Détail d\'un téléphone',
        summary: 'Détail produit',
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Détail produit'
            ),
            new OA\Response(
                response: 401,
                description: 'Non authentifié'
            ),
            new OA\Response(
                response: 404,
                description: 'Produit non trouvé',
            ),
        ]
    )]



    public function show(int $id, Request $request): JsonResponse
    {
        $product = $this->productRepository->find($id);

        if (!$product) {
            return $this->json(['error' => 'Product not found'], 404);
        }

        $productData = [
            ...$this->normalizeProduct($product, ['product:read']),
            '_links' => [
                'self' => $request->getSchemeAndHttpHost() . '/api/products/' . $product->getId(),
                'list' => $request->getSchemeAndHttpHost() . '/api/products'
            ]
        ];

        $response = new JsonResponse($productData);

        $response->setMaxAge(3600);
        $response->headers->set('Cache-Control', 'public, max-age=7200');

        return $response;
    }

    private function normalizeProduct(Product $product, array $groups = ['product:list']): array
    {
        return json_decode($this->container->get('serializer')->serialize($product, 'json', ['groups' => $groups]), true);
    }
}
