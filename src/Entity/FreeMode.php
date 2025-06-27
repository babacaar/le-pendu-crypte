<?php

namespace App\Entity;

use App\Repository\FreeModeRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FreeModeRepository::class)]
class FreeMode extends Game
{
  
    #[ORM\Column(length: 25)]
    private ?string $word = null;

    #[ORM\Column(length: 25, nullable: true)]
    private ?string $hint = null;


    public function getWord(): ?string
    {
        return $this->word;
    }

    public function setWord(string $word): static
    {
        $this->word = $word;

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

   
}

