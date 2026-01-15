<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Entidad Usuario
 * Representa a los usuarios de la aplicación de chat
 * Incluye campos de geolocalización (lat, lng) para el filtrado por distancia
 */
#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'user')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    private ?string $email = null;

    #[ORM\Column(length: 100)]
    private ?string $name = null;

    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column]
    private ?string $password = null;

    /**
     * Latitud de la ubicación del usuario
     * Usada para calcular distancia con fórmula Haversine
     */
    #[ORM\Column(type: 'decimal', precision: 10, scale: 8, nullable: true)]
    private ?string $lat = null;

    /**
     * Longitud de la ubicación del usuario
     * Usada para calcular distancia con fórmula Haversine
     */
    #[ORM\Column(type: 'decimal', precision: 11, scale: 8, nullable: true)]
    private ?string $lng = null;

    /**
     * Indica si el usuario está actualmente online
     */
    #[ORM\Column]
    private ?bool $online = false;

    /**
     * Chats donde el usuario es miembro
     */
    #[ORM\OneToMany(targetEntity: ChatMember::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $chatMembers;

    /**
     * Mensajes enviados por el usuario
     */
    #[ORM\OneToMany(targetEntity: Message::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $messages;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $lastActivity = null;

    public function __construct()
    {
        $this->chatMembers = new ArrayCollection();
        $this->messages = new ArrayCollection();
        $this->roles = ['ROLE_USER'];
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';
        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;
        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;
        return $this;
    }

    public function eraseCredentials(): void
    {
        // Si almacenas datos temporales sensibles en el usuario, límpialos aquí
    }

    public function getLat(): ?string
    {
        return $this->lat;
    }

    public function setLat(?string $lat): static
    {
        $this->lat = $lat;
        return $this;
    }

    public function getLng(): ?string
    {
        return $this->lng;
    }

    public function setLng(?string $lng): static
    {
        $this->lng = $lng;
        return $this;
    }

    public function isOnline(): ?bool
    {
        return $this->online;
    }

    public function setOnline(bool $online): static
    {
        $this->online = $online;
        return $this;
    }

    /**
     * @return Collection<int, ChatMember>
     */
    public function getChatMembers(): Collection
    {
        return $this->chatMembers;
    }

    public function addChatMember(ChatMember $chatMember): static
    {
        if (!$this->chatMembers->contains($chatMember)) {
            $this->chatMembers->add($chatMember);
            $chatMember->setUser($this);
        }
        return $this;
    }

    public function removeChatMember(ChatMember $chatMember): static
    {
        if ($this->chatMembers->removeElement($chatMember)) {
            if ($chatMember->getUser() === $this) {
                $chatMember->setUser(null);
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
            $message->setUser($this);
        }
        return $this;
    }

    public function removeMessage(Message $message): static
    {
        if ($this->messages->removeElement($message)) {
            if ($message->getUser() === $this) {
                $message->setUser(null);
            }
        }
        return $this;
    }

    public function getLastActivity(): ?\DateTimeInterface
    {
        return $this->lastActivity;
    }

    public function setLastActivity(?\DateTimeInterface $lastActivity): static
    {
        $this->lastActivity = $lastActivity;
        return $this;
    }
}
