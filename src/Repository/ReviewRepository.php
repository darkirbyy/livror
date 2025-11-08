<?php

declare(strict_types=1);

namespace App\Repository;

use App\Dto\QueryParam;
use App\Entity\Main\Review;
use App\Service\QueryParamHelper;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ReviewRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, private QueryParamHelper $queryParamHelper)
    {
        parent::__construct($registry, Review::class);
    }

    public function findIndex(QueryParam $queryParam, int $userId): array
    {
        // Define allowed sort and filter parameters and the conversion to the doctrine field
        $sortsConversion = ['name' => 'g.name', 'rating' => 'r.rating', 'hourSpend' => 'r.hourSpend', 'firstPlay' => 'r.firstPlay'];
        $filtersConversion = [];

        // Validate and complete the parameters
        $this->queryParamHelper->load($queryParam, 'review-index');
        $this->queryParamHelper->defaults($queryParam, ['name' => 'asc'], []);
        $this->queryParamHelper->validate($queryParam, array_keys($sortsConversion), array_keys($filtersConversion));
        $this->queryParamHelper->save($queryParam, 'review-index');

        // Build the base query (with select, join and group)
        $qb = $this->createQueryBuilder('r');
        $qb->leftJoin('r.game', 'g')->where('r.userId = :userId')->setParameter('userId', $userId);

        // Apply sorts, filters, offset and limit
        $this->queryParamHelper->applyToQb($queryParam, $qb, $sortsConversion, $filtersConversion);

        // Execute and fetch the query
        return $qb->getQuery()->getResult();
    }

    public function findUsersId(): array
    {
        $qb = $this->createQueryBuilder('r');
        $qb->select('r.userId')->distinct();

        return $qb->getQuery()->getSingleColumnResult();
    }
}
