<?php

namespace App\Service;

use App\Entity\GameSession;
use App\Entity\PointSystem;
use Doctrine\ORM\EntityManagerInterface;

class PointSystemService
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Calculate points based on difficulty, errors, and hint usage.
     */
    public function calculatePoints(string $difficulty, int $errorCount, bool $hintUsed): int
    {
        $basePoints = match ($difficulty) {
            'easy'   => 50,
            'medium' => 100,
            'hard'   => 150,
            default  => throw new \InvalidArgumentException("Unknown difficulty “{$difficulty}”"),
        };
    
        $penalty    = ($errorCount * 2) + ($hintUsed ? 5 : 0);
        $finalScore = max(0, $basePoints - $penalty);
    
        // Only give the +10 perfect bonus on medium & hard
        if ($errorCount === 0 && !$hintUsed && $difficulty !== 'easy') {
            $finalScore += 10;
        }
    
        return $finalScore;
    }

    /**
     * Update the user's PointSystem after winning a round.
     */
    public function updateUserPoints(PointSystem $pointSystem, GameSession $gameSession, int $pointsEarned, string $mode): void
    {
        // Add to total points
        $pointSystem->setTotalPoints($pointSystem->getTotalPoints() + $pointsEarned);

        // Add to either FreeMode or StoryMode points
        if ($mode === 'free') {
            $pointSystem->setFreeModePoints($pointSystem->getFreeModePoints() + $pointsEarned);
        } elseif ($mode === 'story') {
            $pointSystem->setStoryModePoints($pointSystem->getStoryModePoints() + $pointsEarned);
        }
        $gameSession->setSessionPoints($gameSession->getSessionPoints() + $pointsEarned);

        // Save the updated points
        $this->entityManager->persist($pointSystem);
        $this->entityManager->persist($gameSession);
        $this->entityManager->flush();
    }
}