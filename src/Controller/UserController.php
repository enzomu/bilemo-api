<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Client;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Nelmio\ApiDocBundle\Attribute\Model;

#[Route('/api/users', name: 'api_users_')]
#[OA\Tag(name: 'Users')]
class UserController extends AbstractController
{
    public function __construct(
        private UserRepository $userRepository,
        private ValidatorInterface $validator,
        private EntityManagerInterface $entityManager
    ) {}

    #[Route('', name: 'list', methods: ['GET'])]
    #[OA\Get(
        path: '/api/users',
        description: 'Récupère les utilisateurs du client authentifié avec recherche optionnelle',
        summary: 'Liste des utilisateurs du client',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'search',
                description: 'Recherche dans nom, prénom ou email',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', example: 'john')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Liste des utilisateurs du client',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(ref: new Model(type: User::class, groups: ['user:list']))
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
    public function list(Request $request, #[CurrentUser] Client $client): JsonResponse
    {
        $search = $request->query->get('search', '');

        $queryBuilder = $this->userRepository->createQueryBuilder('u')
            ->where('u.client = :client')
            ->setParameter('client', $client)
            ->orderBy('u.createdAt', 'DESC');

        if ($search) {
            $queryBuilder
                ->andWhere('u.firstName LIKE :search OR u.lastName LIKE :search OR u.email LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        $users = $queryBuilder->getQuery()->getResult();

        return $this->json($users, 200, [], ['groups' => ['user:list']]);
    }

    #[Route('/{id}', name: 'show', requirements: ['id' => '\d+'], methods: ['GET'])]
    #[OA\Get(
        path: '/api/users/{id}',
        description: 'Récupère les informations détaillées d\'un utilisateur appartenant au client authentifié',
        summary: 'Détail d\'un utilisateur du client',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'Identifiant de l\'utilisateur',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', minimum: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Détails de l\'utilisateur',
                content: new OA\JsonContent(ref: new Model(type: User::class, groups: ['user:read']))
            ),
            new OA\Response(
                response: 404,
                description: 'Utilisateur non trouvé ou n\'appartient pas au client',
                content: new OA\JsonContent(
                    properties: [
                        'error' => new OA\Property(property: 'error', type: 'string', example: 'User not found')
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
    public function show(int $id, #[CurrentUser] Client $client): JsonResponse
    {
        $user = $this->userRepository->createQueryBuilder('u')
            ->where('u.id = :id')
            ->andWhere('u.client = :client')
            ->setParameter('id', $id)
            ->setParameter('client', $client)
            ->getQuery()
            ->getOneOrNullResult();

        if (!$user) {
            return $this->json(['error' => 'User not found'], 404);
        }

        return $this->json($user, 200, [], ['groups' => ['user:read']]);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    #[OA\Post(
        path: '/api/users',
        description: 'Ajoute un nouvel utilisateur au client authentifié',
        summary: 'Créer un utilisateur',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            description: 'Données du nouvel utilisateur',
            required: true,
            content: new OA\JsonContent(
                required: ['firstName', 'lastName', 'email'],
                properties: [
                    'firstName' => new OA\Property(
                        property: 'firstName',
                        description: 'Prénom de l\'utilisateur (2-100 caractères, lettres uniquement)',
                        type: 'string',
                        maxLength: 100,
                        minLength: 2,
                        example: 'John'
                    ),
                    'lastName' => new OA\Property(
                        property: 'lastName',
                        description: 'Nom de famille (2-100 caractères, lettres uniquement)',
                        type: 'string',
                        maxLength: 100,
                        minLength: 2,
                        example: 'Doe'
                    ),
                    'email' => new OA\Property(
                        property: 'email',
                        description: 'Adresse email unique pour ce client',
                        type: 'string',
                        format: 'email',
                        maxLength: 180,
                        example: 'john.doe@example.com'
                    )
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Utilisateur créé avec succès',
                content: new OA\JsonContent(ref: new Model(type: User::class, groups: ['user:read']))
            ),
            new OA\Response(
                response: 400,
                description: 'Données invalides ou JSON malformé',
                content: new OA\JsonContent(
                    properties: [
                        'errors' => new OA\Property(
                            property: 'errors',
                            type: 'object',
                            example: ['firstName' => 'Le prénom est obligatoire', 'email' => 'Email invalide']
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 409,
                description: 'Email déjà utilisé pour ce client',
                content: new OA\JsonContent(
                    properties: [
                        'error' => new OA\Property(property: 'error', type: 'string', example: 'Email already exists for this client')
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
    public function create(Request $request, #[CurrentUser] Client $client): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return $this->json(['error' => 'Invalid JSON'], 400);
        }

        if (isset($data['email'])) {
            $existingUser = $this->userRepository->createQueryBuilder('u')
                ->where('u.email = :email')
                ->andWhere('u.client = :client')
                ->setParameter('email', $data['email'])
                ->setParameter('client', $client)
                ->getQuery()
                ->getOneOrNullResult();

            if ($existingUser) {
                return $this->json(['error' => 'Email already exists for this client'], 409);
            }
        }

        $user = new User();
        $user->setFirstName($data['firstName'] ?? '')
            ->setLastName($data['lastName'] ?? '')
            ->setEmail($data['email'] ?? '')
            ->setClient($client);

        $errors = $this->validator->validate($user);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            return $this->json(['errors' => $errorMessages], 400);
        }

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $this->json($user, 201, [], ['groups' => ['user:read']]);
    }

    #[Route('/{id}', name: 'delete', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    #[OA\Delete(
        path: '/api/users/{id}',
        description: 'Supprime définitivement un utilisateur du client authentifié',
        summary: 'Supprimer un utilisateur',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'Identifiant de l\'utilisateur à supprimer',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', minimum: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 204,
                description: 'Utilisateur supprimé avec succès'
            ),
            new OA\Response(
                response: 404,
                description: 'Utilisateur non trouvé ou n\'appartient pas au client',
                content: new OA\JsonContent(
                    properties: [
                        'error' => new OA\Property(property: 'error', type: 'string', example: 'User not found')
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
    public function delete(int $id, #[CurrentUser] Client $client): JsonResponse
    {
        $user = $this->userRepository->createQueryBuilder('u')
            ->where('u.id = :id')
            ->andWhere('u.client = :client')
            ->setParameter('id', $id)
            ->setParameter('client', $client)
            ->getQuery()
            ->getOneOrNullResult();

        if (!$user) {
            return $this->json(['error' => 'User not found'], 404);
        }

        $this->entityManager->remove($user);
        $this->entityManager->flush();

        return $this->json(null, 204);
    }
}
