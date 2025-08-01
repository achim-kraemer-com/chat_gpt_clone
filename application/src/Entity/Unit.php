<?php

namespace App\Entity;

use App\Repository\UnitRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UnitRepository::class)]
class Unit
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    private ?string $chatGptApiToken = null;

    #[ORM\OneToMany(mappedBy: 'unit', targetEntity: User::class)]
    private Collection $users;

    #[ORM\Column]
    private ?int $chatCount = 3;

    public function __construct()
    {
        $this->users = new ArrayCollection();
        $this->chatCount = 3;
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getChatGptApiToken(): ?string
    {
        return $this->chatGptApiToken;
    }

    public function setChatGptApiToken(string $chatGptApiToken): static
    {
        $this->chatGptApiToken = $chatGptApiToken;

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function addUser(User $user): static
    {
        if (!$this->users->contains($user)) {
            $this->users->add($user);
            $user->setUnit($this);
        }

        return $this;
    }

    public function removeUser(User $user): static
    {
        if ($this->users->removeElement($user)) {
            // set the owning side to null (unless already changed)
            if ($user->getUnit() === $this) {
                $user->setUnit(null);
            }
        }

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
