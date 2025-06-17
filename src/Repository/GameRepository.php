<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Main\Game;
use App\Entity\Main\Review;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

class GameRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Game::class);
    }

    public function findSortLimit(string $sortField, string $sortOrder, int $firstResult, int $maxResults): array
    {
        // Validate the input parameters, as they come from the user
        $allowedSortFields = ['id', 'name', 'releaseYear'];
        if (!in_array($sortField, $allowedSortFields)) {
            throw new \InvalidArgumentException('Invalid field for sorting');
        }

        $allowedSortOrder = ['asc', 'desc'];
        if (!in_array($sortOrder, $allowedSortOrder)) {
            throw new \InvalidArgumentException('Invalid order for sorting');
        }

        if ($firstResult < 0) {
            throw new \InvalidArgumentException('Invalid first result offset');
        }

        if ($maxResults <= 0) {
            throw new \InvalidArgumentException('Invalid max results limit');
        }

        // Build the query (fetch one more result to determine is there are more to fetch)
        $qb = $this->createQueryBuilder('g');
        $qb->orderBy('g.' . $sortField, strtoupper($sortOrder))
            ->setFirstResult($firstResult)
            ->setMaxResults($maxResults + 1);

        // Execute and fetch the query
        return $qb->getQuery()->getResult();
    }

    public function findNotCommented(int $userId): QueryBuilder
    {
        $qb = $this->createQueryBuilder('g');
        $qb->leftJoin(Review::class, 'r', Join::WITH, 'r.game = g.id and r.userId = :userId')
            ->where('r.id IS NULL')
            ->setParameter('userId', $userId);

        return $qb;
    }
}
