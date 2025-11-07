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
        $filtersConversion = ['users' => 'rf.userId'];

        // Validate and complete the parameters
        $this->queryParamHelper->defaults($queryParam, ['name' => 'asc'], []);
        $this->queryParamHelper->validate($queryParam, array_keys($sortsConversion), array_keys($filtersConversion));

        // Build the base query (with select, join and group)
        $qb = $this->createQueryBuilder('g');
        $qb->leftJoin('g.reviews', 'ra')->select('NEW App\Dto\GameIndex(g, AVG(ra.rating), SUM(ra.hourSpend), MIN(ra.firstPlay))')->groupBy('g.id');

        // Apply the query param : sorts
        foreach ($queryParam->sorts as $key => $direction) {
            $qb->addOrderBy($sortsConversion[$key], strtoupper($direction));
        }
        $qb->addOrderBy('g.id', 'ASC');

        // Apply the query param : filters
        // todo : rethink the logic/query
        // foreach ( as $key => $values) {
        //     $qb->innerJoin('g.reviews', 'rf', Join::WITH, $filtersConversion[$key] . ' IN (:' . $key . ')')->setParameter($key, $values);
        // }

        if (array_key_exists('users', $queryParam->filters)) {
            // prettier-ignore
            $condition = 'NOT EXISTS (SELECT 1 FROM ' . Review::class . ' r0 WHERE r0.game = g)
                           OR EXISTS (SELECT 1 FROM ' . Review::class . ' r1 WHERE r1.game = g AND r1.userId IN (:users))';
            $qb->andWhere($condition)->setParameter('users', $queryParam->filters['users']);
        }

        // Apply the query param : limit and offset
        $qb->setMaxResults($queryParam->limit + 1)->setFirstResult($queryParam->offset);

        // Execute and fetch the query
        return $qb->getQuery()->getResult();
    }

    public function findNotCommented(int $userId): array
    {
        $qb = $this->createQueryBuilder('g');
        $qb->leftJoin('g.reviews', 'r', Join::WITH, 'r.userId = :userId')
            ->where('r.id IS NULL')
            ->setParameter('userId', $userId)
            ->orderBy('g.name', 'ASC')
            ->addOrderBy('g.id', 'ASC');

        return $qb->getQuery()->getResult();
    }

    public function countNotCommented(int $userId): int
    {
        $qb = $this->createQueryBuilder('g');
        $qb->select('COUNT(g.id)')
            ->leftJoin('g.reviews', 'r', Join::WITH, 'r.userId = :userId')
            ->where('r.id IS NULL')
            ->setParameter('userId', $userId);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }
}
