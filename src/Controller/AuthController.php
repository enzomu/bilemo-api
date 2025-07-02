<?php

namespace App\Controller;

use App\Repository\ClientRepository;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Attributes as OA;
use Nelmio\ApiDocBundle\Attribute\Model;


#[OA\Tag(name: 'Authentication')]
class AuthController extends AbstractController
{
    #[Route('/api/auth/login', name: 'api_login', methods: ['POST'])]
    #[OA\Post(
        path: '/api/auth/login',
        description: 'Authentifie un client et retourne un token JWT',
        summary: 'Connexion client',
        requestBody: new OA\RequestBody(
            description: 'Email du client à authentifier',
            required: true,
            content: new OA\JsonContent(
                required: ['email'],
                properties: [
                    'email' => new OA\Property(
                        property: 'email',
                        description: 'Email du client enregistré',
                        type: 'string',
                        format: 'email',
                        example: 'test@techstore.com'
                    )
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Token JWT généré avec succès',
                content: new OA\JsonContent(
                    properties: [
                        'token' => new OA\Property(
                            description: 'Token JWT à utiliser dans les en-têtes Authorization',
                            type: 'string',
                            example: 'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...'
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Email manquant dans la requête',
                content: new OA\JsonContent(ref: '#/components/schemas/Error')
            ),
            new OA\Response(
                response: 401,
                description: 'Client non trouvé ou inactif',
                content: new OA\JsonContent(ref: '#/components/schemas/Error')
            )
        ]
    )]
    public function login(
        Request $request,
        ClientRepository $clientRepository,
        JWTTokenManagerInterface $jwtManager
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['email'])) {
            return new JsonResponse(['error' => 'Email required'], 400);
        }

        $client = $clientRepository->findActiveByEmail($data['email']);

        if (!$client) {
            return new JsonResponse(['error' => 'Client not found or inactive'], 401);
        }

        $token = $jwtManager->create($client);

        return new JsonResponse(['token' => $token]);
    }
}
