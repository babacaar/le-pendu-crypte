<?php

namespace App\Entity;

use App\Repository\ChapterRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ChapterRepository::class)]
class Chapter
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $chapterText = null;

    #[ORM\Column(length: 25)]
    private ?string $wordToGuess = null;

    #[ORM\Column(length: 125, nullable: true)]
    private ?string $hint = null;

    #[ORM\Column]
    private ?int $chapterNumber = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\ManyToOne(inversedBy: 'chapters')]
    #[ORM\JoinColumn(nullable: false)]
    private ?StoryMode $storyMode = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getChapterText(): ?string
    {
        return $this->chapterText;
    }

    public function setChapterText(string $chapterText): static
    {
        $this->chapterText = $chapterText;

        return $this;
    }

    public function getWordToGuess(): ?string
    {
        return $this->wordToGuess;
    }

    public function setWordToGuess(string $wordToGuess): static
    {
        $this->wordToGuess = $wordToGuess;

        return $this;
    }

    public function getHint(): ?string
    {
        return $this->hint;
    }

    public function setHint(?string $hint): static
    {
        $this->hint = $hint;

        return $this;
    }

    public function getChapterNumber(): ?int
    {
        return $this->chapterNumber;
    }

    public function setChapterNumber(int $chapterNumber): static
    {
        $this->chapterNumber = $chapterNumber;

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

    public function getStoryMode(): ?StoryMode
    {
        return $this->storyMode;
    }

    public function setStoryMode(?StoryMode $storyMode): static
    {
        $this->storyMode = $storyMode;

        return $this;
    }
}
