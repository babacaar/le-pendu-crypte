<?php

namespace App\Repository;

use App\Entity\GameSession;
use App\Entity\StoryMode;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<GameSession>
 */
class GameSessionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GameSession::class);
    }

    public function save(GameSession $gameSession, bool $flush = false): void
    {
        $this->_em->persist($gameSession);

        if ($flush) {
            $this->_em->flush();
        }
    }

    public function findActiveFreeModeSessionForUser(User $user): ?GameSession
    {
        return $this->createQueryBuilder('gs')
            ->join('gs.game', 'g')
            ->where('gs.isComplete = false')
            ->andWhere('g INSTANCE OF App\Entity\FreeMode')
            ->andWhere('gs.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findActiveStorySessionsForUser(User $user): array
    {
    
        return $this->createQueryBuilder('gs')
            ->innerJoin('gs.game', 'g')
            ->where('gs.isComplete = false')
            ->andWhere('gs.user = :user')
            ->andWhere('g INSTANCE OF App\Entity\StoryMode') 
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();
    }

    public function findActiveStorySessions(User $user, StoryMode $story): ?GameSession
    {
        return $this->createQueryBuilder('gs')
            ->andWhere('gs.user = :user')
            ->andWhere('gs.game = :story') 
            ->andWhere('gs.isComplete = false')
            ->setParameter('user', $user)
            ->setParameter('story', $story)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult(); 
    }

    public function countActiveGames(): int
    {
        return $this->createQueryBuilder('g')
            ->select('COUNT(g.id)')
            ->where('g.isComplete = false')
            ->getQuery()
            ->getSingleScalarResult();
    }

    //    /**
    //     * @return GameSession[] Returns an array of GameSession objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('g')
    //            ->andWhere('g.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('g.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?GameSession
    //    {
    //        return $this->createQueryBuilder('g')
    //            ->andWhere('g.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
