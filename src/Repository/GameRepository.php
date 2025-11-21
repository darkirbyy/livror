<?php

declare(strict_types=1);

namespace App\Repository;

use App\Dto\QueryParam;
use App\Entity\Main\Game;
use App\Entity\Main\Review;
use App\Service\QueryParamHelper;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\ManagerRegistry;

class GameRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, private QueryParamHelper $queryParamHelper)
    {
        parent::__construct($registry, Game::class);
    }

    public function findIndex(QueryParam $queryParam): array
    {
        // Define allowed sort and filter parameters and the conversion to the doctrine field
        $sortsConversion = ['name' => 'g.name', 'avgRating' => 'AVG(ra.rating)', 'totHourSpend' => 'SUM(ra.hourSpend)', 'minFirstPlay' => 'MIN(ra.firstPlay)'];
        $filtersConversion = ['users' => 'rf.userId', 'withoutReview' => 'todo'];

        // Validate and complete the parameters
        $this->queryParamHelper->load($queryParam, 'game-index');
        $this->queryParamHelper->defaults($queryParam, ['name' => 'asc'], ['withoutReview' => []]);
        $this->queryParamHelper->validate($queryParam, array_keys($sortsConversion), array_keys($filtersConversion));
        $this->queryParamHelper->save($queryParam, 'game-index');

        // Build the base query (with select, join and group)
        $qb = $this->createQueryBuilder('g');
        $qb->leftJoin('g.reviews', 'ra')->select('NEW App\Dto\GameIndex(g, AVG(ra.rating), SUM(ra.hourSpend), MIN(ra.firstPlay))')->groupBy('g.id');

        // Apply alls the query param but filters and add last sort by id
        $this->queryParamHelper->applyButFiltersToQb($queryParam, $qb, $sortsConversion);
        $qb->addOrderBy('g.id', 'ASC');

        if (array_key_exists('users', $queryParam->filters)) {
            $condition = 'EXISTS (SELECT 1 FROM ' . Review::class . ' r1 WHERE r1.game = g AND r1.userId IN (:users))';
            $qb->andWhere($condition)->setParameter('users', $queryParam->filters['users']);
        }
        if (array_key_exists('withoutReview', $queryParam->filters) && !empty($queryParam->filters['withoutReview'])) {
            $condition = 'NOT EXISTS (SELECT 1 FROM ' . Review::class . ' r0 WHERE r0.game = g)';
            $qb->orWhere($condition);
        }

        // Execute and fetch the query
        return $qb->getQuery()->getResult();
    }

    public function findWithoutReview(int $userId): array
    {
        $qb = $this->createQueryBuilder('g');
        $qb->leftJoin('g.reviews', 'r', Join::WITH, 'r.userId = :userId')
            ->where('r.id IS NULL')
            ->setParameter('userId', $userId)
            ->orderBy('g.name', 'ASC')
            ->addOrderBy('g.id', 'ASC');

        return $qb->getQuery()->getResult();
    }

    public function countWithoutReview(int $userId): int
    {
        $qb = $this->createQueryBuilder('g');
        $qb->select('COUNT(g.id)')
            ->leftJoin('g.reviews', 'r', Join::WITH, 'r.userId = :userId')
            ->where('r.id IS NULL')
            ->setParameter('userId', $userId);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }
}
