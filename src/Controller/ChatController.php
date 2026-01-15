<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\ChatMemberRepository;
use App\Repository\ChatRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

/**
 * Controlador principal del chat
 * Endpoints: home, general, actualizar ubicación
 */
#[Route('/api')]
class ChatController extends AbstractController
{
    public function __construct(
        private UserRepository $userRepository,
        private ChatRepository $chatRepository,
        private ChatMemberRepository $chatMemberRepository
    ) {}

    /**
     * Endpoint /api/home
     * Retorna datos del usuario actual y usuarios cercanos (< 5 km)
     * 
     * GET /api/home
     * 
     * Response: {
     *   "user": { ... },
     *   "nearbyUsers": [ ... ]
     * }
     */
    #[Route('/home', name: 'api_home', methods: ['GET'])]
    public function home(#[CurrentUser] ?User $user): JsonResponse
    {
        if (!$user) {
            return $this->json(['error' => 'No autenticado'], Response::HTTP_UNAUTHORIZED);
        }

        // Datos del usuario actual
        $userData = [
            'id' => $user->getId(),
            'name' => $user->getName(),
            'email' => $user->getEmail(),
            'online' => $user->isOnline(),
            'lat' => $user->getLat(),
            'lng' => $user->getLng()
        ];

        // Buscar usuarios cercanos (dentro de 5 km) si el usuario tiene ubicación
        $nearbyUsers = [];
        if ($user->getLat() && $user->getLng()) {
            $nearbyUsersEntities = $this->userRepository->findUsersWithinRadius(
                (float)$user->getLat(),
                (float)$user->getLng(),
                5.0,
                $user
            );

            $nearbyUsers = array_map(fn($nearUser) => [
                'id' => $nearUser->getId(),
                'name' => $nearUser->getName(),
                'email' => $nearUser->getEmail(),
                'online' => $nearUser->isOnline(),
                'distance' => $nearUser->distance ?? 0
            ], $nearbyUsersEntities);
        }

        return $this->json([
            'user' => $userData,
            'nearbyUsers' => $nearbyUsers,
            'nearbyCount' => count($nearbyUsers)
        ]);
    }

    /**
     * Endpoint /api/general
     * Retorna información del chat general
     * 
     * GET /api/general
     */
    #[Route('/general', name: 'api_general', methods: ['GET'])]
    public function general(#[CurrentUser] ?User $user): JsonResponse
    {
        if (!$user) {
            return $this->json(['error' => 'No autenticado'], Response::HTTP_UNAUTHORIZED);
        }

        $generalChat = $this->chatRepository->getGeneralChat();

        if (!$generalChat) {
            return $this->json(['error' => 'Chat general no encontrado'], Response::HTTP_NOT_FOUND);
        }

        // Añadir al usuario al chat general si no es miembro
        $this->chatMemberRepository->addUserToChat($user, $generalChat);

        // Obtener últimos mensajes (usaremos un servicio más adelante)
        $messages = [];
        foreach ($generalChat->getMessages()->slice(0, 50) as $message) {
            $messages[] = [
                'id' => $message->getId(),
                'text' => $message->getText(),
                'user' => [
                    'id' => $message->getUser()->getId(),
                    'name' => $message->getUser()->getName()
                ],
                'createdAt' => $message->getCreatedAt()->format('Y-m-d H:i:s')
            ];
        }

        return $this->json([
            'chat' => [
                'id' => $generalChat->getId(),
                'type' => $generalChat->getType(),
                'isActive' => $generalChat->isActive()
            ],
            'messages' => array_reverse($messages)
        ]);
    }

    /**
     * Endpoint /api/actualizar
     * Actualiza la ubicación del usuario
     * 
     * POST /api/actualizar
     * Body: {
     *   "lat": 40.4168,
     *   "lng": -3.7038
     * }
     */
    #[Route('/actualizar', name: 'api_update_location', methods: ['POST'])]
    public function updateLocation(Request $request, #[CurrentUser] ?User $user): JsonResponse
    {
        if (!$user) {
            return $this->json(['error' => 'No autenticado'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);

        if (!isset($data['lat'], $data['lng'])) {
            return $this->json(['error' => 'Faltan parámetros lat y lng'], Response::HTTP_BAD_REQUEST);
        }

        $lat = (float)$data['lat'];
        $lng = (float)$data['lng'];

        // Validar coordenadas
        if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
            return $this->json(['error' => 'Coordenadas inválidas'], Response::HTTP_BAD_REQUEST);
        }

        $this->userRepository->updateLocation($user, $lat, $lng);

        return $this->json([
            'message' => 'Ubicación actualizada',
            'lat' => $user->getLat(),
            'lng' => $user->getLng()
        ]);
    }

    /**
     * Endpoint /api/logout
     * Marca al usuario como offline
     * 
     * POST /api/logout
     */
    #[Route('/logout', name: 'api_logout', methods: ['POST'])]
    public function logout(#[CurrentUser] ?User $user): JsonResponse
    {
        if (!$user) {
            return $this->json(['error' => 'No autenticado'], Response::HTTP_UNAUTHORIZED);
        }

        $this->userRepository->updateOnlineStatus($user, false);

        return $this->json(['message' => 'Sesión cerrada']);
    }
}
