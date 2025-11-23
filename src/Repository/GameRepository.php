<?php

declare(strict_types=1);

namespace App\Repository;

use App\Dto\QueryParam;
use App\Entity\Main\Game;
use App\Enum\TypeGameEnum;
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
        $filtersConversion = ['typeGame' => '', 'users' => '', 'withoutReview' => ''];

        // Validate and complete the parameters
        $this->queryParamHelper->load($queryParam, 'game-index');
        $this->queryParamHelper->defaults($queryParam, ['name' => 'asc'], ['typeGame' => [TypeGameEnum::GAME->value, TypeGameEnum::DLC->value], 'withoutReview' => []]);
        $this->queryParamHelper->validate($queryParam, array_keys($sortsConversion), array_keys($filtersConversion));
        $this->queryParamHelper->save($queryParam, 'game-index');

        // Build the base query (with select, join and group)
        $qb = $this->createQueryBuilder('g');
        $qb->leftJoin('g.reviews', 'ra')->select('NEW App\Dto\GameIndex(g, AVG(ra.rating), SUM(ra.hourSpend), MIN(ra.firstPlay))')->groupBy('g.id');

        // Apply alls the query param but filters and add last sort by id
        $this->queryParamHelper->applyButFiltersToQb($queryParam, $qb, $sortsConversion);
        $qb->addOrderBy('g.id', 'ASC');

        if (array_key_exists('typeGame', $queryParam->filters)) {
            $qb->where('g.typeGame IN (:typeGame)')->setParameter('typeGame', $queryParam->filters['typeGame']);
        }

        // Filter logic : at least one of the reviews is in the filter list
        // if (array_key_exists('users', $queryParam->filters)) {
        //     $conditions[] = 'EXISTS (SELECT 1 FROM ' . Review::class . ' r1 WHERE r1.game = g AND r1.userId IN (:users))';
        //     $qb->setParameter('users', $queryParam->filters['users']);
        // }
        // if (array_key_exists('withoutReview', $queryParam->filters) && !empty($queryParam->filters['withoutReview'])) {
        //     $conditions[] = 'NOT EXISTS (SELECT 1 FROM ' . Review::class . ' r0 WHERE r0.game = g)';
        // }
        // if(!empty($conditions)){
        //     $qb->andWhere(implode(' OR ', $conditions));
        // }

        // Filter logic : all the reviews are in the filter list
        if (array_key_exists('users', $queryParam->filters)) {
            $qb->leftJoin('g.reviews', 'rf', Join::WITH, 'rf.userId IN (:users)')
                ->andHaving('COUNT(DISTINCT ra.id) = COUNT(DISTINCT rf.id)')
                ->setParameter('users', $queryParam->filters['users']);
        }
        if (!array_key_exists('withoutReview', $queryParam->filters) || empty($queryParam->filters['withoutReview'])) {
            $qb->andHaving('COUNT(ra.id) > 0');
        }

        // Execute and fetch the query
        return $qb->getQuery()->getResult();
    }

    public function findPatternWithoutReview(int $userId, string $pattern, int $limit): array
    {
        $qb = $this->createQueryBuilder('g');
        $qb->leftJoin('g.reviews', 'r', Join::WITH, 'r.userId = :userId')
            ->where('r.id IS NULL')
            ->setParameter('userId', $userId)
            ->addSelect('MATCH_AGAINST(g.name, :pattern) as HIDDEN relevance')
            ->andWhere('MATCH_AGAINST(g.name, :pattern) > 0')
            ->setParameter('pattern', $pattern)
            ->orderBy('relevance', 'DESC')
            ->addOrderBy('g.name', 'ASC')
            ->setMaxResults($limit);

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
