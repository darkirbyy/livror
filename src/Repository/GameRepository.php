<?php

declare(strict_types=1);

namespace App\Repository;

use App\Dto\GameInfo;
use App\Dto\QueryParam;
use App\Entity\Main\Game;
use App\Entity\Main\Review;
use App\Enum\DateFieldEnum;
use App\Service\QueryParamHelper;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
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
        $this->queryParamHelper->defaults($queryParam, ['name' => 'asc'], ['typeGame' => [], 'users' => [], 'withoutReview' => []]);
        $this->queryParamHelper->validate($queryParam, array_keys($sortsConversion), array_keys($filtersConversion));
        $this->queryParamHelper->save($queryParam, 'game-index');

        // Build the base query (with select, join and group)
        $qb = $this->createQueryBuilder('g');
        $this->selectDto($qb);

        // Apply alls the query param but filters and add last sort by id
        $this->queryParamHelper->applyButFiltersToQb($queryParam, $qb, $sortsConversion);
        $qb->addOrderBy('g.id', 'ASC');

        // Filter logic : type of game, users that have reviewed, without review
        if (array_key_exists('typeGame', $queryParam->filters) && !empty($queryParam->filters['typeGame'])) {
            $qb->where('g.typeGame IN (:typeGame)')->setParameter('typeGame', $queryParam->filters['typeGame']);
        }

        if (array_key_exists('users', $queryParam->filters) && !empty($queryParam->filters['users'])) {
            // prettier-ignore
            $conditionsOr[] = 'EXISTS (SELECT 1 FROM ' . Review::class . ' ru WHERE ru.game = g AND ru.userId IN (:users)
                               GROUP BY ru.game HAVING COUNT(DISTINCT ru.userId) = :userCount)';
            $qb->setParameter('users', $queryParam->filters['users']);
            $qb->setParameter('userCount', count($queryParam->filters['users']));
        } else {
            $conditionsOr[] = 'EXISTS (SELECT 1 FROM ' . Review::class . ' ru WHERE ru.game = g)';
        }

        if (array_key_exists('withoutReview', $queryParam->filters) && !empty($queryParam->filters['withoutReview'])) {
            $conditionsOr[] = 'NOT EXISTS (SELECT 1 FROM ' . Review::class . ' rw WHERE rw.game = g)';
        }

        $qb->andWhere(implode(' OR ', $conditionsOr));

        // Execute and fetch the query
        return $qb->getQuery()->getResult();
    }

    public function findShow(Game $game): GameInfo
    {
        // Build the base query (with select, join and group)
        $qb = $this->createQueryBuilder('g');
        $this->selectDto($qb);

        // Only retrieve the required game
        $qb->where('g.id = :id')->setParameter('id', $game->getId());

        // Execute and fetch the query
        return $qb->getQuery()->getSingleResult();
    }

    public function findPattern(string $pattern, int $limit, ?int $userId): array
    {
        // Build the base query (with select, join and group)
        $qb = $this->createQueryBuilder('g');
        $this->selectDto($qb);

        // Restrict to game not reviewed by user with userId (if not null)
        if (!is_null($userId)) {
            $qb->leftJoin('g.reviews', 'rf', Join::WITH, 'rf.userId = :userId')->where('rf.id IS NULL')->setParameter('userId', $userId);
        }

        // Select and limit by relevance thanks to mariadb fulltext search
        $qb->addSelect('MATCH_AGAINST(g.name, :pattern) as HIDDEN relevance')
            ->andWhere('MATCH_AGAINST(g.name, :pattern) > 0')
            ->setParameter('pattern', $pattern)
            ->orderBy('relevance', 'DESC')
            ->addOrderBy('g.name', 'ASC')
            ->setMaxResults($limit);

        // Execute and fetch the query
        return $qb->getQuery()->getResult();
    }

    public function findLike(string $like, int $limit, ?int $userId): array
    {
        // Build the base query (with select, join and group)
        $qb = $this->createQueryBuilder('g');
        $this->selectDto($qb);

        // Restrict to game not reviewed by user with userId (if not null)
        if (!is_null($userId)) {
            $qb->leftJoin('g.reviews', 'rf', Join::WITH, 'rf.userId = :userId')->where('rf.id IS NULL')->setParameter('userId', $userId);
        }

        // Select and limit using basic like clause
        $qb->andWhere('g.name LIKE :like')->setParameter('like', $like)->orderBy('g.name', 'ASC')->setMaxResults($limit);

        // Execute and fetch the query
        return $qb->getQuery()->getResult();
    }

    public function findLast(DateFieldEnum $dateField, int $limit): array
    {
        // Build the base query (with select, join and group)
        $qb = $this->createQueryBuilder('g');
        $this->selectDto($qb);

        // Find last ones by the given field
        $qb->orderBy('g.' . $dateField->toDatabaseField(), 'DESC')
            ->addOrderBy('g.id', 'ASC')
            ->setMaxResults($limit);

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
        $qb->select('COUNT(g.id)')->leftJoin('g.reviews', 'r', Join::WITH, 'r.userId = :userId')->where('r.id IS NULL')->setParameter('userId', $userId);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    private function selectDto(QueryBuilder $qb): void
    {
        $qb->leftJoin('g.reviews', 'ra')
            ->select('NEW ' . GameInfo::class . '(g, COUNT(ra), AVG(ra.rating), SUM(ra.hourSpend), MIN(ra.firstPlay))')
            ->groupBy('g.id');
    }
}
