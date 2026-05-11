<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Attachment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class AttachmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Attachment::class);
    }

    public function findOneByName(string $name): mixed
    {
        $qb = $this->createQueryBuilder('a');

        $qb->where('a.fileMeta.name = :name')->setParameter('name', $name);

        return $qb->getQuery()->getOneOrNullResult();
    }
}
