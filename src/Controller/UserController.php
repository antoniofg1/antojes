<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Controlador de gestión de usuarios
 * Proporciona CRUD básico para usuarios
 */
#[Route('/api/usuarios')]
class UserController extends AbstractController
{
    public function __construct(
        private UserRepository $userRepository,
        private EntityManagerInterface $em,
        private UserPasswordHasherInterface $passwordHasher
    ) {}

    /**
     * Lista todos los usuarios
     * GET /api/usuarios
     */
    #[Route('', name: 'api_users_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $users = $this->userRepository->findAll();
        
        $data = array_map(fn($user) => [
            'id' => $user->getId(),
            'name' => $user->getName(),
            'email' => $user->getEmail(),
            'online' => $user->isOnline(),
            'lat' => $user->getLat(),
            'lng' => $user->getLng()
        ], $users);

        return $this->json($data);
    }

    /**
     * Obtiene un usuario por ID
     * GET /api/usuarios/{id}
     */
    #[Route('/{id}', name: 'api_users_show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $user = $this->userRepository->find($id);

        if (!$user) {
            return $this->json(['error' => 'Usuario no encontrado'], Response::HTTP_NOT_FOUND);
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

    /**
     * Crea un nuevo usuario
     * POST /api/usuarios
     * 
     * Body: {
     *   "name": "Juan Pérez",
     *   "email": "juan@example.com",
     *   "password": "123456"
     * }
     */
    #[Route('', name: 'api_users_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['name'], $data['email'], $data['password'])) {
            return $this->json(['error' => 'Faltan campos requeridos'], Response::HTTP_BAD_REQUEST);
        }

        // Verificar si el email ya existe
        $existingUser = $this->userRepository->findOneByEmail($data['email']);
        if ($existingUser) {
            return $this->json(['error' => 'El email ya está registrado'], Response::HTTP_CONFLICT);
        }

        $user = new User();
        $user->setName($data['name']);
        $user->setEmail($data['email']);
        $user->setPassword($this->passwordHasher->hashPassword($user, $data['password']));
        $user->setOnline(false);

        $this->em->persist($user);
        $this->em->flush();

        return $this->json([
            'id' => $user->getId(),
            'name' => $user->getName(),
            'email' => $user->getEmail(),
            'message' => 'Usuario creado exitosamente'
        ], Response::HTTP_CREATED);
    }

    /**
     * Actualiza un usuario
     * PUT /api/usuarios/{id}
     */
    #[Route('/{id}', name: 'api_users_update', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $user = $this->userRepository->find($id);

        if (!$user) {
            return $this->json(['error' => 'Usuario no encontrado'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['name'])) {
            $user->setName($data['name']);
        }

        if (isset($data['email'])) {
            $user->setEmail($data['email']);
        }

        if (isset($data['password'])) {
            $user->setPassword($this->passwordHasher->hashPassword($user, $data['password']));
        }

        $this->em->flush();

        return $this->json([
            'id' => $user->getId(),
            'name' => $user->getName(),
            'email' => $user->getEmail(),
            'message' => 'Usuario actualizado exitosamente'
        ]);
    }

    /**
     * Elimina un usuario
     * DELETE /api/usuarios/{id}
     */
    #[Route('/{id}', name: 'api_users_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $user = $this->userRepository->find($id);

        if (!$user) {
            return $this->json(['error' => 'Usuario no encontrado'], Response::HTTP_NOT_FOUND);
        }

        $this->em->remove($user);
        $this->em->flush();

        return $this->json(['message' => 'Usuario eliminado exitosamente']);
    }
}
