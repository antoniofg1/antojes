<?php

namespace App\Entity;

use App\Repository\ChatMemberRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Entidad ChatMember
 * Representa la relación entre un usuario y un chat
 * - Un usuario puede pertenecer a múltiples chats
 * - Un chat puede tener múltiples miembros
 * - leftAt indica cuándo el usuario abandonó el chat (null = aún en el chat)
 */
#[ORM\Entity(repositoryClass: ChatMemberRepository::class)]
#[ORM\Table(name: 'chat_member')]
class ChatMember
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * Chat al que pertenece esta membresía
     */
    #[ORM\ManyToOne(targetEntity: Chat::class, inversedBy: 'members')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Chat $chat = null;

    /**
     * Usuario que es miembro del chat
     */
    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'chatMembers')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    /**
     * Fecha en que el usuario se unió al chat
     */
    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $joinedAt = null;

    /**
     * Fecha en que el usuario abandonó el chat
     * Si es null, el usuario aún está en el chat
     */
    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $leftAt = null;

    public function __construct()
    {
        $this->joinedAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getChat(): ?Chat
    {
        return $this->chat;
    }

    public function setChat(?Chat $chat): static
    {
        $this->chat = $chat;
        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getJoinedAt(): ?\DateTimeInterface
    {
        return $this->joinedAt;
    }

    public function setJoinedAt(\DateTimeInterface $joinedAt): static
    {
        $this->joinedAt = $joinedAt;
        return $this;
    }

    public function getLeftAt(): ?\DateTimeInterface
    {
        return $this->leftAt;
    }

    public function setLeftAt(?\DateTimeInterface $leftAt): static
    {
        $this->leftAt = $leftAt;
        return $this;
    }

    /**
     * Verifica si el usuario sigue activo en el chat
     */
    public function isActive(): bool
    {
        return $this->leftAt === null;
    }
}
