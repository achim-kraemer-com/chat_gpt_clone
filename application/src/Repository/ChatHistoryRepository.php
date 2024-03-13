<?php

namespace App\Repository;

use App\Entity\ChatHistory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ChatHistory>
 *
 * @method ChatHistory|null find($id, $lockMode = null, $lockVersion = null)
 * @method ChatHistory|null findOneBy(array $criteria, array $orderBy = null)
 * @method ChatHistory[]    findAll()
 * @method ChatHistory[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ChatHistoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ChatHistory::class);
    }

    public function getHistoryFromUnit(int $unitId)
    {
        return $this->createQueryBuilder('ch') 
            ->join('ch.user','u')
            ->where('u.unit = :unitId')
            ->setParameter('unitId', $unitId)
            ->getQuery()
            ->getResult();
    }
}
