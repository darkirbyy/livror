<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Main\Review;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\ParameterType;
use Doctrine\Persistence\ManagerRegistry;

class ReviewRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Review::class);
    }

    public function findSortLimit(int $userId, string $sortField, string $sortOrder, int $firstResult, int $maxResults): array
    {
        // Validate the input parameters, as they come from the user
        $allowedSortFields = ['id' => 'r.id', 'name' => 'g.name', 'mark' => 'r.mark', 'hourSpend' => 'r.hourSpend', 'firstPlay' => 'r.firstPlay'];
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
        $qb = $this->createQueryBuilder('r');
        $qb->leftJoin('r.game', 'g')
            ->where('r.userId = :userId')
            ->setParameter('userId', $userId, ParameterType::INTEGER)
            ->orderBy($allowedSortFields[$sortField], $allowedSortOrders[$sortOrder])
            ->setFirstResult($firstResult)
            ->setMaxResults($maxResults + 1);

        // Execute and fetch the query
        return $qb->getQuery()->getResult();
    }
}
