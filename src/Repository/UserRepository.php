<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Account\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function byUsersId(array $usersId): array
    {
        // Fetch the users and index the result by the id
        $qb = $this->createQueryBuilder('u');
        $qb->indexBy('u', 'u.id')->where('u.id IN (:ids)')->setParameter('ids', $usersId);
        $results = $qb->getQuery()->getResult();

        // Create an array with all requested users and replace the fetched-ones
        $users = array_fill_keys($usersId, null);
        $users = array_replace($users, $results);

        return $users;
    }
}
