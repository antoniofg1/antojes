<?php

namespace App\Entity;

use App\Repository\ChatRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Entidad Chat
 * Representa un chat (general o privado)
 * - El chat GENERAL siempre tiene id = 1
 * - Los chats PRIVADOS son temporales entre dos usuarios
 */
#[ORM\Entity(repositoryClass: ChatRepository::class)]
#[ORM\Table(name: 'chat')]
class Chat
{
    public const TYPE_GENERAL = 'GENERAL';
    public const TYPE_PRIVATE = 'PRIVATE';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * Tipo de chat: GENERAL o PRIVATE
     */
    #[ORM\Column(length: 20)]
    private ?string $type = null;

    /**
     * Indica si el chat está activo
     * Un chat privado se marca como inactivo cuando ambos usuarios lo abandonan
     */
    #[ORM\Column]
    private ?bool $isActive = true;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $createdAt = null;

    /**
     * Miembros del chat
     */
    #[ORM\OneToMany(targetEntity: ChatMember::class, mappedBy: 'chat', orphanRemoval: true)]
    private Collection $members;

    /**
     * Mensajes del chat
     */
    #[ORM\OneToMany(targetEntity: Message::class, mappedBy: 'chat', orphanRemoval: true)]
    private Collection $messages;

    public function __construct()
    {
        $this->members = new ArrayCollection();
        $this->messages = new ArrayCollection();
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;
        return $this;
    }

    public function isActive(): ?bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    /**
     * @return Collection<int, ChatMember>
     */
    public function getMembers(): Collection
    {
        return $this->members;
    }

    public function addMember(ChatMember $member): static
    {
        if (!$this->members->contains($member)) {
            $this->members->add($member);
            $member->setChat($this);
        }
        return $this;
    }

    public function removeMember(ChatMember $member): static
    {
        if ($this->members->removeElement($member)) {
            if ($member->getChat() === $this) {
                $member->setChat(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, Message>
     */
    public function getMessages(): Collection
    {
        return $this->messages;
    }

    public function addMessage(Message $message): static
    {
        if (!$this->messages->contains($message)) {
            $this->messages->add($message);
            $message->setChat($this);
        }
        return $this;
    }

    public function removeMessage(Message $message): static
    {
        if ($this->messages->removeElement($message)) {
            if ($message->getChat() === $this) {
                $message->setChat(null);
            }
        }
        return $this;
    }

    /**
     * Verifica si es el chat general (id = 1)
     */
    public function isGeneral(): bool
    {
        return $this->id === 1 && $this->type === self::TYPE_GENERAL;
    }
}
