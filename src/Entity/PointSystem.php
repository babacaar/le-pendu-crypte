<?php

namespace App\Entity;

use App\Repository\PointSystemRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PointSystemRepository::class)]
class PointSystem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(options:['default' => 0])]
    private ?int $totalPoints = 0;

    #[ORM\Column(nullable: true, options:['default' => 0])]
    private ?int $storyModePoints = null;

    #[ORM\Column(nullable: true, options:['default' => 0])]
    private ?int $freeModePoints = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;
    #[ORM\OneToOne(inversedBy: 'pointSystem', cascade: ["persist", "remove"])]
    #[ORM\JoinColumn(nullable: false, onDelete: "CASCADE")]
    private ?User $user = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTotalPoints(): ?int
    {
        return $this->totalPoints;
    }

    public function setTotalPoints(int $totalPoints): static
    {
        $this->totalPoints = $totalPoints;

        return $this;
    }

    public function getStoryModePoints(): ?int
    {
        return $this->storyModePoints;
    }

    public function setStoryModePoints(?int $storyModePoints): static
    {
        $this->storyModePoints = $storyModePoints;

        return $this;
    }

    public function getFreeModePoints(): ?int
    {
        return $this->freeModePoints;
    }

    public function setFreeModePoints(?int $freeModePoints): static
    {
        $this->freeModePoints = $freeModePoints;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): static
    {
        // set the owning side of the relation if necessary
        if ($user->getPointSystem() !== $this) {
            $user->setPointSystem($this);
        }

        $this->user = $user;

        return $this;
    }
}
