<?php

declare(strict_types=1);

namespace App\Repository;

use App\Dto\QueryParam;
use App\Entity\Review;
use App\Enum\DateFieldEnum;
use App\Service\QueryParamHelper;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\ParameterType;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

class ReviewRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, private QueryParamHelper $queryParamHelper)
    {
        parent::__construct($registry, Review::class);
    }

    public function findIndex(QueryParam $queryParam, Uuid $userUuid): array
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
        $this->joinGameAndUser($qb, $userUuid);

        // Apply the query param
        $this->queryParamHelper->applyButFiltersToQb($queryParam, $qb, $sortsConversion);
        $this->applyFiltersToQb($queryParam, $qb);

        // Execute and fetch the query
        return $qb->getQuery()->getResult();
    }

    public function countIndex(QueryParam $queryParam, Uuid $userUuid): array
    {
        // Count the displayed (with filters) number of reviews
        $qb = $this->createQueryBuilder('r')->select('COUNT(r.id)');
        $this->joinGameAndUser($qb, $userUuid);
        $this->applyFiltersToQb($queryParam, $qb);
        $displayed = $qb->getQuery()->getSingleScalarResult();

        // Count the total number of reviews
        $qb = $this->createQueryBuilder('r')->select('COUNT(r.id)');
        $this->joinGameAndUser($qb, $userUuid);
        $total = $qb->getQuery()->getSingleScalarResult();

        return ['displayed' => $displayed, 'total' => $total];
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

    public function findTitleSince(\DateTime $dateTime, Uuid $userUuid): array
    {
        // Build the base query (with select, join and group)
        $qb = $this->createQueryBuilder('r');
        $this->joinGameAndUser($qb, $userUuid);
        $qb->select('g.name');

        // Find all since given datetime, ordered by name
        $qb->andWhere('r.dateAdd >= :dateTime')->setParameter('dateTime', $dateTime)->orderBy('g.name', 'ASC');

        // Execute and fetch the query
        return $qb->getQuery()->getSingleColumnResult();
    }

    public function countByUserUuid(): array
    {
        $qb = $this->createQueryBuilder('r');
        $qb->select('r.userUuid as userUuid, COUNT(r.id) as numberReviews')->groupBy('r.userUuid');

        return $qb->getQuery()->getResult();
    }

    public function findDelete(Uuid $userUuid): array
    {
        // Build the base query (with select, join and group)
        $qb = $this->createQueryBuilder('r');
        $this->joinGameAndUser($qb, $userUuid);

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

    private function joinGameAndUser(QueryBuilder $qb, Uuid $userUuid): void
    {
        $qb->leftJoin('r.game', 'g')->where('r.userUuid = :userUuid')->setParameter('userUuid', $userUuid->toBinary(), ParameterType::BINARY);
    }
}
