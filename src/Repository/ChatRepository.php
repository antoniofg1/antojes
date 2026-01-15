<?php

namespace App\Repository;

use App\Entity\Chat;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository de Chat
 * Métodos para gestionar chats generales y privados
 * 
 * @extends ServiceEntityRepository<Chat>
 */
class ChatRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Chat::class);
    }

    /**
     * Obtiene el chat general (id = 1)
     * Este chat siempre debe existir
     */
    public function getGeneralChat(): ?Chat
    {
        return $this->find(1);
    }

    /**
     * Busca un chat privado entre dos usuarios
     * @return Chat|null El chat privado activo entre los dos usuarios, o null si no existe
     */
    public function findPrivateChatBetweenUsers(User $user1, User $user2): ?Chat
    {
        return $this->createQueryBuilder('c')
            ->innerJoin('c.members', 'm1')
            ->innerJoin('c.members', 'm2')
            ->where('c.type = :type')
            ->andWhere('c.isActive = :active')
            ->andWhere('m1.user = :user1')
            ->andWhere('m2.user = :user2')
            ->andWhere('m1.leftAt IS NULL')
            ->andWhere('m2.leftAt IS NULL')
            ->setParameter('type', Chat::TYPE_PRIVATE)
            ->setParameter('active', true)
            ->setParameter('user1', $user1)
            ->setParameter('user2', $user2)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Obtiene todos los chats activos de un usuario
     * Incluye el chat general y los chats privados donde el usuario es miembro activo
     * 
     * @return Chat[]
     */
    public function findActiveChatsForUser(User $user): array
    {
        return $this->createQueryBuilder('c')
            ->innerJoin('c.members', 'm')
            ->where('m.user = :user')
            ->andWhere('m.leftAt IS NULL')
            ->andWhere('c.isActive = :active')
            ->setParameter('user', $user)
            ->setParameter('active', true)
            ->orderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Obtiene solo los chats privados activos de un usuario
     * 
     * @return Chat[]
     */
    public function findPrivateChatsForUser(User $user): array
    {
        return $this->createQueryBuilder('c')
            ->innerJoin('c.members', 'm')
            ->where('m.user = :user')
            ->andWhere('m.leftAt IS NULL')
            ->andWhere('c.isActive = :active')
            ->andWhere('c.type = :type')
            ->setParameter('user', $user)
            ->setParameter('active', true)
            ->setParameter('type', Chat::TYPE_PRIVATE)
            ->orderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Verifica si un usuario es miembro activo de un chat
     */
    public function isUserMemberOfChat(User $user, Chat $chat): bool
    {
        $result = $this->createQueryBuilder('c')
            ->innerJoin('c.members', 'm')
            ->where('c.id = :chatId')
            ->andWhere('m.user = :user')
            ->andWhere('m.leftAt IS NULL')
            ->setParameter('chatId', $chat->getId())
            ->setParameter('user', $user)
            ->getQuery()
            ->getOneOrNullResult();

        return $result !== null;
    }

    /**
     * Crea el chat general (debe llamarse durante la instalación)
     */
    public function createGeneralChat(): Chat
    {
        $chat = new Chat();
        $chat->setType(Chat::TYPE_GENERAL);
        $chat->setIsActive(true);
        
        $em = $this->getEntityManager();
        $em->persist($chat);
        $em->flush();

        return $chat;
    }

    /**
     * Crea un chat privado entre dos usuarios
     */
    public function createPrivateChat(User $user1, User $user2): Chat
    {
        $chat = new Chat();
        $chat->setType(Chat::TYPE_PRIVATE);
        $chat->setIsActive(true);

        $em = $this->getEntityManager();
        $em->persist($chat);
        $em->flush();

        return $chat;
    }

    /**
     * Desactiva un chat (marca como inactivo)
     */
    public function deactivateChat(Chat $chat): void
    {
        $chat->setIsActive(false);
        
        $em = $this->getEntityManager();
        $em->persist($chat);
        $em->flush();
    }
}
