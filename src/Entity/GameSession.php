<?php

namespace App\Entity;

use App\Repository\GameSessionRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GameSessionRepository::class)]
class GameSession
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $sessionStartTime = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $sessionEndTime = null;

    #[ORM\Column(options: ['default' => 0])]
    private ?int $sessionPoints = 0;

    #[ORM\Column(nullable: true)]
    private ?int $currentChapter = null;

    #[ORM\Column(length: 30)]
    private ?string $gameType = null;

    #[ORM\Column(options: ['default' => false])]
    private ?bool $isComplete = false;

    #[ORM\ManyToOne(inversedBy: 'gameSessions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne(inversedBy: 'gameSessions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Game $game = null;

    #[ORM\Column(type: "json")]
    private array $usedWords = [];

    public function __construct()
    {
        $this->usedWords = [];
    }


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSessionStartTime(): ?\DateTimeImmutable
    {
        return $this->sessionStartTime;
    }

    public function setSessionStartTime(\DateTimeImmutable $sessionStartTime): static
    {
        $this->sessionStartTime = $sessionStartTime;

        return $this;
    }

    public function getSessionEndTime(): ?\DateTimeImmutable
    {
        return $this->sessionEndTime;
    }

    public function setSessionEndTime(?\DateTimeImmutable $sessionEndTime): static
    {
        $this->sessionEndTime = $sessionEndTime;

        return $this;
    }

    public function getSessionPoints(): ?int
    {
        return $this->sessionPoints;
    }

    public function setSessionPoints(int $sessionPoints): static
    {
        $this->sessionPoints = $sessionPoints;

        return $this;
    }

    public function getCurrentChapter(): ?int
    {
        return $this->currentChapter;
    }

    public function setCurrentChapter(?int $currentChapter): static
    {
        $this->currentChapter = $currentChapter;

        return $this;
    }

    public function getGameType(): ?string
    {
        return $this->gameType;
    }

    public function setGameType(string $gameType): static
    {
        $this->gameType = $gameType;

        return $this;
    }

    public function isComplete(): ?bool
    {
        return $this->isComplete;
    }

    public function setIsComplete(bool $isComplete): static
    {
        $this->isComplete = $isComplete;

        return $this;
    }

    public function getUsedWords(): array
    {
        return $this->usedWords ?? []; 
    }

    public function setUsedWords(array $usedWords): self
    {
        $this->usedWords = $usedWords ?? []; 
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

    public function getGame(): ?Game
    {
        return $this->game;
    }

    public function setGame(?Game $game): static
    {
        $this->game = $game;

        return $this;
    }
}
