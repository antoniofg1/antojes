<?php

namespace App\Controller;

use App\Entity\Chat;
use App\Entity\User;
use App\Repository\ChatMemberRepository;
use App\Repository\ChatRepository;
use App\Repository\MessageRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

/**
 * Controlador de Chats Privados
 * Gestiona la creación, visualización y gestión de chats privados
 */
#[Route('/api/privado')]
class PrivateChatController extends AbstractController
{
    public function __construct(
        private ChatRepository $chatRepository,
        private ChatMemberRepository $chatMemberRepository,
        private UserRepository $userRepository,
        private MessageRepository $messageRepository,
        private EntityManagerInterface $em
    ) {}

    /**
     * Endpoint /api/privado
     * Lista todos los chats privados activos del usuario
     * 
     * GET /api/privado
     */
    #[Route('', name: 'api_private_chats_list', methods: ['GET'])]
    public function list(#[CurrentUser] ?User $user): JsonResponse
    {
        if (!$user) {
            return $this->json(['error' => 'No autenticado'], Response::HTTP_UNAUTHORIZED);
        }

        $chats = $this->chatRepository->findPrivateChatsForUser($user);

        $data = [];
        foreach ($chats as $chat) {
            $otherUser = $this->chatMemberRepository->getOtherUserInPrivateChat($chat, $user);
            $lastMessage = $this->messageRepository->getLastMessage($chat);

            $data[] = [
                'id' => $chat->getId(),
                'type' => $chat->getType(),
                'isActive' => $chat->isActive(),
                'otherUser' => $otherUser ? [
                    'id' => $otherUser->getId(),
                    'name' => $otherUser->getName(),
                    'email' => $otherUser->getEmail(),
                    'online' => $otherUser->isOnline()
                ] : null,
                'lastMessage' => $lastMessage ? [
                    'text' => $lastMessage->getText(),
                    'createdAt' => $lastMessage->getCreatedAt()->format('Y-m-d H:i:s')
                ] : null
            ];
        }

        return $this->json(['chats' => $data]);
    }

    /**
     * Endpoint /api/invitar
     * Crea un chat privado entre el usuario actual y otro usuario
     * o devuelve el existente si ya hay uno
     * 
     * POST /api/invitar
     * Body: { "userId": 5 }
     */
    #[Route('/invitar', name: 'api_private_chat_invite', methods: ['POST'])]
    public function invite(Request $request, #[CurrentUser] ?User $user): JsonResponse
    {
        if (!$user) {
            return $this->json(['error' => 'No autenticado'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);

        if (!isset($data['userId'])) {
            return $this->json(['error' => 'Falta el parámetro userId'], Response::HTTP_BAD_REQUEST);
        }

        $otherUser = $this->userRepository->find($data['userId']);

        if (!$otherUser) {
            return $this->json(['error' => 'Usuario no encontrado'], Response::HTTP_NOT_FOUND);
        }

        if ($otherUser->getId() === $user->getId()) {
            return $this->json(['error' => 'No puedes crear un chat contigo mismo'], Response::HTTP_BAD_REQUEST);
        }

        // Buscar si ya existe un chat entre estos usuarios
        $existingChat = $this->chatRepository->findPrivateChatBetweenUsers($user, $otherUser);

        if ($existingChat) {
            return $this->json([
                'message' => 'Chat existente encontrado',
                'chat' => [
                    'id' => $existingChat->getId(),
                    'type' => $existingChat->getType(),
                    'isActive' => $existingChat->isActive()
                ]
            ]);
        }

        // Crear nuevo chat privado
        $chat = $this->chatRepository->createPrivateChat($user, $otherUser);

        // Añadir ambos usuarios al chat
        $this->chatMemberRepository->addUserToChat($user, $chat);
        $this->chatMemberRepository->addUserToChat($otherUser, $chat);

        return $this->json([
            'message' => 'Chat privado creado',
            'chat' => [
                'id' => $chat->getId(),
                'type' => $chat->getType(),
                'isActive' => $chat->isActive(),
                'otherUser' => [
                    'id' => $otherUser->getId(),
                    'name' => $otherUser->getName(),
                    'email' => $otherUser->getEmail()
                ]
            ]
        ], Response::HTTP_CREATED);
    }

    /**
     * Endpoint /api/privado/{id}
     * Obtiene los mensajes de un chat privado específico
     * 
     * GET /api/privado/{id}
     */
    #[Route('/{id}', name: 'api_private_chat_show', methods: ['GET'])]
    public function show(int $id, #[CurrentUser] ?User $user): JsonResponse
    {
        if (!$user) {
            return $this->json(['error' => 'No autenticado'], Response::HTTP_UNAUTHORIZED);
        }

        $chat = $this->chatRepository->find($id);

        if (!$chat) {
            return $this->json(['error' => 'Chat no encontrado'], Response::HTTP_NOT_FOUND);
        }

        // Verificar que el usuario es miembro del chat
        if (!$this->chatRepository->isUserMemberOfChat($user, $chat)) {
            return $this->json(['error' => 'No tienes acceso a este chat'], Response::HTTP_FORBIDDEN);
        }

        $messages = $this->messageRepository->findLatestMessages($chat, 50);

        $messagesData = array_map(fn($message) => [
            'id' => $message->getId(),
            'text' => $message->getText(),
            'user' => [
                'id' => $message->getUser()->getId(),
                'name' => $message->getUser()->getName()
            ],
            'createdAt' => $message->getCreatedAt()->format('Y-m-d H:i:s')
        ], $messages);

        $otherUser = $this->chatMemberRepository->getOtherUserInPrivateChat($chat, $user);

        return $this->json([
            'chat' => [
                'id' => $chat->getId(),
                'type' => $chat->getType(),
                'isActive' => $chat->isActive(),
                'otherUser' => $otherUser ? [
                    'id' => $otherUser->getId(),
                    'name' => $otherUser->getName(),
                    'online' => $otherUser->isOnline()
                ] : null
            ],
            'messages' => array_reverse($messagesData)
        ]);
    }

    /**
     * Endpoint /api/privado/salir
     * El usuario abandona un chat privado
     * Si ambos usuarios abandonan, el chat se marca como inactivo
     * 
     * POST /api/privado/salir
     * Body: { "chatId": 3 }
     */
    #[Route('/salir', name: 'api_private_chat_leave', methods: ['POST'])]
    public function leave(Request $request, #[CurrentUser] ?User $user): JsonResponse
    {
        if (!$user) {
            return $this->json(['error' => 'No autenticado'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);

        if (!isset($data['chatId'])) {
            return $this->json(['error' => 'Falta el parámetro chatId'], Response::HTTP_BAD_REQUEST);
        }

        $chat = $this->chatRepository->find($data['chatId']);

        if (!$chat) {
            return $this->json(['error' => 'Chat no encontrado'], Response::HTTP_NOT_FOUND);
        }

        if ($chat->getType() !== Chat::TYPE_PRIVATE) {
            return $this->json(['error' => 'Solo se puede salir de chats privados'], Response::HTTP_BAD_REQUEST);
        }

        // Marcar que el usuario ha salido
        $this->chatMemberRepository->removeUserFromChat($user, $chat);

        // Verificar si todos los miembros han salido
        if ($this->chatMemberRepository->areAllMembersGone($chat)) {
            $this->chatRepository->deactivateChat($chat);
            $message = 'Has salido del chat. El chat ha sido cerrado';
        } else {
            $message = 'Has salido del chat';
        }

        return $this->json(['message' => $message]);
    }

    /**
     * Endpoint /api/privado/cambiar/chat
     * Cambia el chat activo del usuario (útil para frontend)
     * 
     * POST /api/privado/cambiar/chat
     * Body: { "chatId": 3 }
     */
    #[Route('/cambiar/chat', name: 'api_private_chat_switch', methods: ['POST'])]
    public function switchChat(Request $request, #[CurrentUser] ?User $user): JsonResponse
    {
        if (!$user) {
            return $this->json(['error' => 'No autenticado'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);

        if (!isset($data['chatId'])) {
            return $this->json(['error' => 'Falta el parámetro chatId'], Response::HTTP_BAD_REQUEST);
        }

        $chat = $this->chatRepository->find($data['chatId']);

        if (!$chat) {
            return $this->json(['error' => 'Chat no encontrado'], Response::HTTP_NOT_FOUND);
        }

        // Verificar que el usuario es miembro
        if (!$this->chatRepository->isUserMemberOfChat($user, $chat)) {
            return $this->json(['error' => 'No eres miembro de este chat'], Response::HTTP_FORBIDDEN);
        }

        return $this->json([
            'message' => 'Chat cambiado',
            'chatId' => $chat->getId()
        ]);
    }
}
