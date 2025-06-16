<?php

namespace App\Controller;

use App\Repository\ClientRepository;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class AuthController extends AbstractController
{
    #[Route('/api/auth/login', name: 'api_login', methods: ['POST'])]
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
