<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\ChatRepository;
use App\Repository\MessageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

/**
 * Controlador de Mensajes
 * Gestiona el envío y recepción de mensajes
 */
#[Route('/api')]
class MessageController extends AbstractController
{
    public function __construct(
        private MessageRepository $messageRepository,
        private ChatRepository $chatRepository
    ) {}

    /**
     * Endpoint /api/mensaje
     * 
     * GET: Obtiene mensajes de un chat
     * POST: Envía un mensaje a un chat
     * 
     * GET /api/mensaje?chatId=1&limit=50
     * POST /api/mensaje
     * Body: {
     *   "chat_id": 1,
     *   "text": "Hola mundo"
     * }
     */
    #[Route('/mensaje', name: 'api_message', methods: ['GET', 'POST'])]
    public function message(Request $request, #[CurrentUser] ?User $user): JsonResponse
    {
        if (!$user) {
            return $this->json(['error' => 'No autenticado'], Response::HTTP_UNAUTHORIZED);
        }

        if ($request->getMethod() === 'GET') {
            return $this->getMessages($request, $user);
        }

        return $this->sendMessage($request, $user);
    }

    /**
     * Obtiene mensajes de un chat
     */
    private function getMessages(Request $request, User $user): JsonResponse
    {
        $chatId = $request->query->get('chatId');
        $limit = $request->query->get('limit', 50);

        if (!$chatId) {
            return $this->json(['error' => 'Falta el parámetro chatId'], Response::HTTP_BAD_REQUEST);
        }

        $chat = $this->chatRepository->find($chatId);

        if (!$chat) {
            return $this->json(['error' => 'Chat no encontrado'], Response::HTTP_NOT_FOUND);
        }

        // Verificar que el usuario es miembro del chat
        if (!$this->chatRepository->isUserMemberOfChat($user, $chat)) {
            return $this->json(['error' => 'No tienes acceso a este chat'], Response::HTTP_FORBIDDEN);
        }

        $messages = $this->messageRepository->findLatestMessages($chat, (int)$limit);

        $data = array_map(fn($message) => [
            'id' => $message->getId(),
            'text' => $message->getText(),
            'user' => [
                'id' => $message->getUser()->getId(),
                'name' => $message->getUser()->getName()
            ],
            'createdAt' => $message->getCreatedAt()->format('Y-m-d H:i:s')
        ], $messages);

        return $this->json([
            'messages' => array_reverse($data),
            'count' => count($data)
        ]);
    }

    /**
     * Envía un mensaje a un chat
     */
    private function sendMessage(Request $request, User $user): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['chat_id'], $data['text'])) {
            return $this->json(['error' => 'Faltan parámetros chat_id o text'], Response::HTTP_BAD_REQUEST);
        }

        $chatId = (int)$data['chat_id'];
        $text = trim($data['text']);

        if (empty($text)) {
            return $this->json(['error' => 'El mensaje no puede estar vacío'], Response::HTTP_BAD_REQUEST);
        }

        $chat = $this->chatRepository->find($chatId);

        if (!$chat) {
            return $this->json(['error' => 'Chat no encontrado'], Response::HTTP_NOT_FOUND);
        }

        // Verificar que el usuario es miembro del chat
        if (!$this->chatRepository->isUserMemberOfChat($user, $chat)) {
            return $this->json(['error' => 'No eres miembro de este chat'], Response::HTTP_FORBIDDEN);
        }

        // Crear el mensaje
        $message = $this->messageRepository->createMessage($chat, $user, $text);

        return $this->json([
            'message' => 'Mensaje enviado',
            'data' => [
                'id' => $message->getId(),
                'text' => $message->getText(),
                'user' => [
                    'id' => $user->getId(),
                    'name' => $user->getName()
                ],
                'createdAt' => $message->getCreatedAt()->format('Y-m-d H:i:s')
            ]
        ], Response::HTTP_CREATED);
    }

    /**
     * Endpoint /api/perfil
     * Obtiene el perfil del usuario actual
     * 
     * GET /api/perfil
     */
    #[Route('/perfil', name: 'api_profile', methods: ['GET'])]
    public function profile(#[CurrentUser] ?User $user): JsonResponse
    {
        if (!$user) {
            return $this->json(['error' => 'No autenticado'], Response::HTTP_UNAUTHORIZED);
        }

        return $this->json([
            'id' => $user->getId(),
            'name' => $user->getName(),
            'email' => $user->getEmail(),
            'online' => $user->isOnline(),
            'lat' => $user->getLat(),
            'lng' => $user->getLng(),
            'lastActivity' => $user->getLastActivity()?->format('Y-m-d H:i:s')
        ]);
    }
}
