<?php

namespace App\Repository;

use App\Entity\StoryMode;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<StoryMode>
 */
class StoryModeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StoryMode::class);
    }

    public function findMostPlayedStory(): ?StoryMode
    {
        $result = $this->createQueryBuilder('s')
            ->select('s') // Only select the entity
            ->leftJoin('s.gameSessions', 'g')
            ->groupBy('s.id')
            ->orderBy('COUNT(g.id)', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        return $result;
    }
    public function findMostPlayedStoryWithCount(): ?array
    {
        return $this->createQueryBuilder('s')
            ->select('s AS story, COUNT(g.id) AS playCount') // Select entity + count
            ->leftJoin('s.gameSessions', 'g')
            ->groupBy('s.id')
            ->orderBy('playCount', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    //    /**
    //     * @return StoryMode[] Returns an array of StoryMode objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('s')
    //            ->andWhere('s.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('s.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?StoryMode
    //    {
    //        return $this->createQueryBuilder('s')
    //            ->andWhere('s.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
