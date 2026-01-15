<?php

namespace App\Repository;

use App\Entity\Chat;
use App\Entity\ChatMember;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository de ChatMember
 * Gestiona la relación entre usuarios y chats
 * 
 * @extends ServiceEntityRepository<ChatMember>
 */
class ChatMemberRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ChatMember::class);
    }

    /**
     * Añade un usuario a un chat
     */
    public function addUserToChat(User $user, Chat $chat): ChatMember
    {
        // Verificar si ya existe una membresía
        $existingMember = $this->findOneBy([
            'user' => $user,
            'chat' => $chat
        ]);

        if ($existingMember) {
            // Si el usuario ya salió, reactivarlo
            if ($existingMember->getLeftAt() !== null) {
                $existingMember->setLeftAt(null);
                $existingMember->setJoinedAt(new \DateTime());
                $this->getEntityManager()->persist($existingMember);
                $this->getEntityManager()->flush();
            }
            return $existingMember;
        }

        // Crear nueva membresía
        $member = new ChatMember();
        $member->setUser($user);
        $member->setChat($chat);
        
        $em = $this->getEntityManager();
        $em->persist($member);
        $em->flush();

        return $member;
    }

    /**
     * Marca que un usuario ha abandonado un chat
     */
    public function removeUserFromChat(User $user, Chat $chat): void
    {
        $member = $this->findOneBy([
            'user' => $user,
            'chat' => $chat,
            'leftAt' => null
        ]);

        if ($member) {
            $member->setLeftAt(new \DateTime());
            $this->getEntityManager()->persist($member);
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Obtiene todos los miembros activos de un chat
     * 
     * @return User[]
     */
    public function getActiveMembersOfChat(Chat $chat): array
    {
        $members = $this->createQueryBuilder('cm')
            ->innerJoin('cm.user', 'u')
            ->where('cm.chat = :chat')
            ->andWhere('cm.leftAt IS NULL')
            ->setParameter('chat', $chat)
            ->getQuery()
            ->getResult();

        return array_map(fn($member) => $member->getUser(), $members);
    }

    /**
     * Obtiene la membresía activa de un usuario en un chat específico
     */
    public function findActiveMembership(User $user, Chat $chat): ?ChatMember
    {
        return $this->findOneBy([
            'user' => $user,
            'chat' => $chat,
            'leftAt' => null
        ]);
    }

    /**
     * Cuenta los miembros activos de un chat
     */
    public function countActiveMembers(Chat $chat): int
    {
        return $this->createQueryBuilder('cm')
            ->select('COUNT(cm.id)')
            ->where('cm.chat = :chat')
            ->andWhere('cm.leftAt IS NULL')
            ->setParameter('chat', $chat)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Verifica si todos los miembros de un chat privado lo han abandonado
     * Si es así, el chat debería marcarse como inactivo
     */
    public function areAllMembersGone(Chat $chat): bool
    {
        $activeCount = $this->countActiveMembers($chat);
        return $activeCount === 0;
    }

    /**
     * Obtiene el otro usuario de un chat privado (el que no es el usuario actual)
     */
    public function getOtherUserInPrivateChat(Chat $chat, User $currentUser): ?User
    {
        $members = $this->getActiveMembersOfChat($chat);
        
        foreach ($members as $member) {
            if ($member->getId() !== $currentUser->getId()) {
                return $member;
            }
        }

        return null;
    }
}
