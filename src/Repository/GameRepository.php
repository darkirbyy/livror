<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Game;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class GameRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Game::class);
    }

    public function findAndSort($sortField, $sortOrder): array
    {
        $allowedFields = ['id', 'name', 'releaseYear'];
        $allowedOrder = ['asc', 'desc'];

        // Validate the input parameters
        if (!in_array($sortField, $allowedFields)) {
            throw new \InvalidArgumentException('Invalid field for sorting');
        }

        if (!in_array($sortOrder, $allowedOrder)) {
            throw new \InvalidArgumentException('Invalid order for sorting');
        }

        $qb = $this->createQueryBuilder('g');
        $qb->orderBy('g.' . $sortField, strtoupper($sortOrder))->setMaxResults(5);

        return $qb->getQuery()->getResult();
    }

    //    public function findOneBySomeField($value): ?Game
    //    {
    //        return $this->createQueryBuilder('g')
    //            ->andWhere('g.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
