<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Main\Review;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ReviewRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Review::class);
    }

    public function findSortLimit(string $sortField, string $sortOrder, int $firstResult, int $maxResults): array
    {
        // Validate the input parameters, as they come from the user
        $allowedSortFields = ['id', 'dateAdd'];
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
        $qb = $this->createQueryBuilder('r');
        $qb->orderBy('r.' . $sortField, strtoupper($sortOrder))
            ->setFirstResult($firstResult)
            ->setMaxResults($maxResults + 1);

        // Execute and fetch the query
        return $qb->getQuery()->getResult();
    }
}
