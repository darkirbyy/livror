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
        $sortsConversion = ['id' => 'g.id', 'name' => 'g.name', 'avgRating' => 'AVG(ra.rating)', 'totHourSpend' => 'SUM(ra.hourSpend)', 'minFirstPlay' => 'MIN(ra.firstPlay)'];
        $filtersConversion = ['users' => 'rf.userId'];

        // Validate and complete the parameters
        $this->queryParamHelper->defaults($queryParam, ['name' => 'asc'], []);
        $this->queryParamHelper->validate($queryParam, array_keys($sortsConversion), array_keys($filtersConversion));

        // Build the base query (with select, join and group)
        $qb = $this->createQueryBuilder('g');
        $qb->leftJoin('g.reviews', 'ra')
            ->select('NEW App\Dto\GameIndex(g, AVG(ra.rating), SUM(ra.hourSpend), MIN(ra.firstPlay))')
            ->groupBy('g.id') // todo : don't work !
            ->innerJoin('g.reviews', 'rf');

        // Apply the parameters (offset, limit, sorts, filters)
        $this->queryParamHelper->apply($queryParam, $qb, $sortsConversion, $filtersConversion);

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
