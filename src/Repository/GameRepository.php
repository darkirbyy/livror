<?php

declare(strict_types=1);

namespace App\Repository;

use App\Dto\GameInfo;
use App\Dto\QueryParam;
use App\Entity\Game;
use App\Entity\Review;
use App\Enum\DateFieldEnum;
use App\Service\QueryParamHelper;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\ParameterType;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

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

        // Apply alls the query param and add last sort by id
        $this->queryParamHelper->applyButFiltersToQb($queryParam, $qb, $sortsConversion);
        $this->applyFiltersToQb($queryParam, $qb);
        $qb->addOrderBy('g.id', 'ASC');

        // Execute and fetch the query
        return $qb->getQuery()->getResult();
    }

    public function countIndex(QueryParam $queryParam): array
    {
        // Count the displayed (with filters) number of games
        $qb = $this->createQueryBuilder('g')->select('COUNT(g.id)');
        $this->applyFiltersToQb($queryParam, $qb);
        $displayed = $qb->getQuery()->getSingleScalarResult();

        // Count the total number of games
        $qb = $this->createQueryBuilder('g')->select('COUNT(g.id)');
        $total = $qb->getQuery()->getSingleScalarResult();

        return ['displayed' => $displayed, 'total' => $total];
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

    public function findPattern(string $pattern, int $limit, ?Uuid $userUuid): array
    {
        // Build the base query (with select, join and group)
        $qb = $this->createQueryBuilder('g');
        $this->selectDto($qb);

        // Restrict to game not reviewed by user with userUuid (if not null)
        if (!is_null($userUuid)) {
            $qb->leftJoin('g.reviews', 'rf', Join::WITH, 'rf.userUuid = :userUuid')->where('rf.id IS NULL')->setParameter('userUuid', $userUuid->toBinary(), ParameterType::BINARY);
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

    public function findLike(string $like, int $limit, ?Uuid $userUuid): array
    {
        // Build the base query (with select, join and group)
        $qb = $this->createQueryBuilder('g');
        $this->selectDto($qb);

        // Restrict to game not reviewed by user with userUuid (if not null)
        if (!is_null($userUuid)) {
            $qb->leftJoin('g.reviews', 'rf', Join::WITH, 'rf.userUuid = :userUuid')->where('rf.id IS NULL')->setParameter('userUuid', $userUuid->toBinary(), ParameterType::BINARY);
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

    public function findSince(\DateTime $dateTime): array
    {
        // Build the base query (games only)
        $qb = $this->createQueryBuilder('g')->select('g.name');

        // Find all since given datetime, ordered by name
        $qb->where('g.dateAdd >= :dateTime')->setParameter('dateTime', $dateTime)->orderBy('g.name', 'ASC');

        // Execute and fetch the query
        return $qb->getQuery()->getSingleColumnResult();
    }

    public function findWithoutReview(Uuid $userUuid): array
    {
        $qb = $this->createQueryBuilder('g');
        $qb->leftJoin('g.reviews', 'r', Join::WITH, 'r.userUuid = :userUuid')
            ->where('r.id IS NULL')
            ->setParameter('userUuid', $userUuid->toBinary(), ParameterType::BINARY)
            ->orderBy('g.name', 'ASC')
            ->addOrderBy('g.id', 'ASC');

        return $qb->getQuery()->getResult();
    }

    public function countWithoutReview(Uuid $userUuid): int
    {
        $qb = $this->createQueryBuilder('g');
        $qb->select('COUNT(g.id)')
            ->leftJoin('g.reviews', 'r', Join::WITH, 'r.userUuid = :userUuid')
            ->where('r.id IS NULL')
            ->setParameter('userUuid', $userUuid->toBinary(), ParameterType::BINARY);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    private function applyFiltersToQb(QueryParam $queryParam, QueryBuilder $qb): QueryBuilder
    {
        // Filter logic : type of game, users that have reviewed, without review
        if (array_key_exists('typeGame', $queryParam->filters) && !empty($queryParam->filters['typeGame'])) {
            $qb->where('g.typeGame IN (:typeGame)')->setParameter('typeGame', $queryParam->filters['typeGame']);
        }

        if (array_key_exists('users', $queryParam->filters) && !empty($queryParam->filters['users'])) {
            // prettier-ignore
            $conditionsOr[] = 'EXISTS (SELECT 1 FROM ' . Review::class . ' ru WHERE ru.game = g AND ru.userUuid IN (:users)
                               GROUP BY ru.game HAVING COUNT(DISTINCT ru.userUuid) = :userCount)';
            $qb->setParameter('users', array_map(fn(string $s) => Uuid::fromString($s)->toBinary(), $queryParam->filters['users']), ArrayParameterType::BINARY);
            $qb->setParameter('userCount', count($queryParam->filters['users']));
        } else {
            $conditionsOr[] = 'EXISTS (SELECT 1 FROM ' . Review::class . ' ru WHERE ru.game = g)';
        }

        if (array_key_exists('withoutReview', $queryParam->filters) && !empty($queryParam->filters['withoutReview'])) {
            $conditionsOr[] = 'NOT EXISTS (SELECT 1 FROM ' . Review::class . ' rw WHERE rw.game = g)';
        }

        $qb->andWhere(implode(' OR ', $conditionsOr));

        return $qb;
    }

    private function selectDto(QueryBuilder $qb): void
    {
        $qb->leftJoin('g.reviews', 'ra')
            ->select('NEW ' . GameInfo::class . '(g, COUNT(ra), AVG(ra.rating), SUM(ra.hourSpend), MIN(ra.firstPlay))')
            ->groupBy('g.id');
    }
}
