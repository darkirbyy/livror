<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Main\Game;
use App\Entity\Main\Review;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr\Join;
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
        $allowedSortFields = ['id' => 'g.id', 'name' => 'g.name', 'avgRating' => 'AVG(r.rating)', 'totHourSpend' => 'SUM(r.hourSpend)', 'minFirstPlay' => 'MIN(r.firstPlay)'];
        if (!array_key_exists($sortField, $allowedSortFields)) {
            throw new \InvalidArgumentException('Invalid field for sorting');
        }

        $allowedSortOrders = ['asc' => 'ASC', 'desc' => 'DESC'];
        if (!array_key_exists($sortOrder, $allowedSortOrders)) {
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
        $qb->leftJoin('g.reviews', 'r')
            ->addSelect()
            ->select('NEW App\Dto\GameIndex(g, AVG(r.rating), SUM(r.hourSpend), MIN(r.firstPlay))')
            ->groupBy('g.id')
            ->orderBy($allowedSortFields[$sortField], $allowedSortOrders[$sortOrder])
            ->setFirstResult($firstResult)
            ->setMaxResults($maxResults + 1);

        // Execute and fetch the query
        return $qb->getQuery()->getResult();
    }

    public function findNotCommented(int $userId): array
    {
        $qb = $this->createQueryBuilder('g');
        $qb->leftJoin(Review::class, 'r', Join::WITH, 'r.game = g.id and r.userId = :userId')
            ->where('r.id IS NULL')
            ->setParameter('userId', $userId)
            ->orderBy('g.name', 'ASC');

        return $qb->getQuery()->getResult();
    }

    public function countNotCommented(int $userId): int
    {
        $qb = $this->createQueryBuilder('g');
        $qb->select('COUNT(g.id)')
            ->leftJoin(Review::class, 'r', Join::WITH, 'r.game = g.id and r.userId = :userId')
            ->where('r.id IS NULL')
            ->setParameter('userId', $userId);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }
}
