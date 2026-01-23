<?php

declare(strict_types=1);

namespace App\Repository;

use App\Dto\QueryParam;
use App\Entity\Main\Review;
use App\Enum\DateFieldEnum;
use App\Service\QueryParamHelper;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
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
        $filtersConversion = ['typeGame' => ''];

        // Validate and complete the parameters
        $this->queryParamHelper->load($queryParam, 'review-index');
        $this->queryParamHelper->defaults($queryParam, ['name' => 'asc'], ['typeGame' => '']);
        $this->queryParamHelper->validate($queryParam, array_keys($sortsConversion), array_keys($filtersConversion));
        $this->queryParamHelper->save($queryParam, 'review-index');

        // Build the base query (with select, join and group)
        $qb = $this->createQueryBuilder('r');
        $qb->leftJoin('r.game', 'g')->where('r.userId = :userId')->setParameter('userId', $userId);

        // Apply the query param
        $this->queryParamHelper->applyButFiltersToQb($queryParam, $qb, $sortsConversion);
        $this->applyFiltersToQb($queryParam, $qb);

        // Execute and fetch the query
        return $qb->getQuery()->getResult();
    }

    public function countIndex(int $userId): ?int
    {
        $qb = $this->createQueryBuilder('r');
        $qb->select('COUNT(r.id)')->where('r.userId = :userId')->setParameter('userId', $userId);

        return $qb->getQuery()->getSingleScalarResult();
    }

    public function countByUserId(): array
    {
        $qb = $this->createQueryBuilder('r');
        $qb->indexBy('r', 'r.userId')->select('r.userId, COUNT(r.id) as numberReviews')->groupBy('r.userId');

        return $qb->getQuery()->getResult();
    }

    public function findLast(DateFieldEnum $dateField, int $limit): array
    {
        // Build the base query
        $qb = $this->createQueryBuilder('r');

        // Find last ones by the given field
        $qb->orderBy('r.' . $dateField->toDatabaseField(), 'DESC')
            ->addOrderBy('r.id', 'ASC')
            ->setMaxResults($limit);

        // Execute and fetch the query
        return $qb->getQuery()->getResult();
    }

    private function applyFiltersToQb(QueryParam $queryParam, QueryBuilder $qb): QueryBuilder
    {
        // Filter logic : type of game
        if (array_key_exists('typeGame', $queryParam->filters) && !empty($queryParam->filters['typeGame'])) {
            $qb->andWhere('g.typeGame IN (:typeGame)')->setParameter('typeGame', $queryParam->filters['typeGame']);
        }

        return $qb;
    }
}
