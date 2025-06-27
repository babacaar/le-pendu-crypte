<?php

namespace App\Repository;

use App\Entity\FreeMode;
use App\Entity\Game;
use App\Entity\GameSession;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FreeMode>
 */
class FreeModeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FreeMode::class);
    }

    public function findUnusedWord(string $difficulty, User $user): ?FreeMode
    {
        // Récupérer tous les mots utilisés dans toutes les sessions FreeMode de l'utilisateur
        $qb = $this->createQueryBuilder('g')
            ->andWhere('g INSTANCE OF App\Entity\FreeMode')
            ->andWhere('g.difficultyLevel = :difficulty')
            ->setParameter('difficulty', $difficulty)
            ->setMaxResults(1);
    
        // Récupérer les mots déjà utilisés dans toutes les sessions FreeMode de l'utilisateur
        $usedWords = $this->getEntityManager()->createQueryBuilder()
            ->select('gs.usedWords')
            ->from(GameSession::class, 'gs')
            ->where('gs.user = :user')
            ->andWhere('gs.gameType = :gameType')
            ->setParameter('user', $user)
            ->setParameter('gameType', 'freemode')
            ->getQuery()
            ->getResult();
    
        // Transformer en un tableau simple
        $usedWordsArray = [];
        foreach ($usedWords as $session) {
            $usedWordsArray = array_merge($usedWordsArray, $session['usedWords'] ?? []);
        }
    
        if (!empty($usedWordsArray)) {
            $qb->andWhere('g.word NOT IN (:usedWords)')
               ->setParameter('usedWords', $usedWordsArray);
        }
    
        return $qb->getQuery()->getOneOrNullResult();
    }

    //    /**
    //     * @return FreeMode[] Returns an array of FreeMode objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('f')
    //            ->andWhere('f.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('f.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?FreeMode
    //    {
    //        return $this->createQueryBuilder('f')
    //            ->andWhere('f.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
