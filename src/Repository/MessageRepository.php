<?php

namespace App\Repository;

use App\Entity\Chat;
use App\Entity\Message;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository de Message
 * Gestiona los mensajes de los chats
 * 
 * @extends ServiceEntityRepository<Message>
 */
class MessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Message::class);
    }

    /**
     * Obtiene los últimos N mensajes de un chat
     * Ordenados del más antiguo al más reciente
     * 
     * @param Chat $chat El chat del cual obtener mensajes
     * @param int $limit Número máximo de mensajes a retornar
     * @return Message[]
     */
    public function findLatestMessages(Chat $chat, int $limit = 50): array
    {
        return $this->createQueryBuilder('m')
            ->where('m.chat = :chat')
            ->setParameter('chat', $chat)
            ->orderBy('m.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Obtiene mensajes de un chat desde una fecha específica
     * Útil para la sincronización y actualizaciones en tiempo real
     * 
     * @return Message[]
     */
    public function findMessagesSince(Chat $chat, \DateTimeInterface $since): array
    {
        return $this->createQueryBuilder('m')
            ->where('m.chat = :chat')
            ->andWhere('m.createdAt > :since')
            ->setParameter('chat', $chat)
            ->setParameter('since', $since)
            ->orderBy('m.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Crea y guarda un nuevo mensaje
     */
    public function createMessage(Chat $chat, User $user, string $text): Message
    {
        $message = new Message();
        $message->setChat($chat);
        $message->setUser($user);
        $message->setText($text);

        $em = $this->getEntityManager();
        $em->persist($message);
        $em->flush();

        return $message;
    }

    /**
     * Obtiene el último mensaje de un chat
     * Útil para mostrar preview en la lista de chats
     */
    public function getLastMessage(Chat $chat): ?Message
    {
        return $this->createQueryBuilder('m')
            ->where('m.chat = :chat')
            ->setParameter('chat', $chat)
            ->orderBy('m.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Cuenta los mensajes de un chat
     */
    public function countMessages(Chat $chat): int
    {
        return $this->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->where('m.chat = :chat')
            ->setParameter('chat', $chat)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Obtiene los mensajes de un chat con paginación
     * 
     * @param Chat $chat
     * @param int $page Página actual (empieza en 1)
     * @param int $perPage Mensajes por página
     * @return Message[]
     */
    public function findPaginatedMessages(Chat $chat, int $page = 1, int $perPage = 50): array
    {
        $offset = ($page - 1) * $perPage;

        return $this->createQueryBuilder('m')
            ->where('m.chat = :chat')
            ->setParameter('chat', $chat)
            ->orderBy('m.createdAt', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($perPage)
            ->getQuery()
            ->getResult();
    }
}
