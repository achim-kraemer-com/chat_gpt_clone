<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[UniqueEntity(fields: ['email'], message: 'There is already an account with this email')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    private ?string $email = null;

    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(type: 'boolean')]
    private bool $isVerified = false;

    #[ORM\ManyToOne(inversedBy: 'users')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Unit $unit = null;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: ChatHistory::class, orphanRemoval: true)]
    private Collection $chatHistories;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $customerInstruction = null;

    #[ORM\Column]
    private ?int $chatCount = 3;

    public function __construct()
    {
        $this->chatHistories = new ArrayCollection();
        $this->chatCount = 3;
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

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function setIsVerified(bool $isVerified): static
    {
        $this->isVerified = $isVerified;

        return $this;
    }

    public function getUnit(): ?Unit
    {
        return $this->unit;
    }

    public function setUnit(?Unit $unit): static
    {
        $this->unit = $unit;

        return $this;
    }

    /**
     * @return Collection<int, ChatHistory>
     */
    public function getChatHistories(): Collection
    {
        return $this->chatHistories;
    }

    public function addChatHistory(ChatHistory $chatHistory): static
    {
        if (!$this->chatHistories->contains($chatHistory)) {
            $this->chatHistories->add($chatHistory);
            $chatHistory->setUser($this);
        }

        return $this;
    }

    public function removeChatHistory(ChatHistory $chatHistory): static
    {
        if ($this->chatHistories->removeElement($chatHistory)) {
            // set the owning side to null (unless already changed)
            if ($chatHistory->getUser() === $this) {
                $chatHistory->setUser(null);
            }
        }

        return $this;
    }

    public function getCustomerInstruction(): ?string
    {
        return $this->customerInstruction;
    }

    public function setCustomerInstruction(?string $customerInstruction): static
    {
        $this->customerInstruction = $customerInstruction;

        return $this;
    }

    public function getChatCount(): ?int
    {
        return $this->chatCount;
    }

    public function setChatCount(int $chatCount): static
    {
        $this->chatCount = $chatCount;

        return $this;
    }
}
