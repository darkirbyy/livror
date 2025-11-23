<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Main\Steam;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class SteamRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Steam::class);
    }

    public function findPattern(string $pattern, int $limit): mixed
    {
        $qb = $this->createQueryBuilder('s');
        $qb->addSelect('MATCH_AGAINST(s.name, :pattern) as HIDDEN relevance')
            ->where('MATCH_AGAINST(s.name, :pattern) > 0')
            ->setParameter('pattern', $pattern)
            ->orderBy('relevance', 'DESC')
            ->addOrderBy('s.name', 'ASC')
            ->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }
}
