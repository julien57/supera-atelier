<?php

namespace App\Repository;

use App\Entity\Event;
use App\Entity\WorkshopDate;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method WorkshopDate|null find($id, $lockMode = null, $lockVersion = null)
 * @method WorkshopDate|null findOneBy(array $criteria, array $orderBy = null)
 * @method WorkshopDate[]    findAll()
 * @method WorkshopDate[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class WorkshopDateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WorkshopDate::class);
    }

    public function getByEvent(Event $event)
    {
        return $this->createQueryBuilder('ws')
            ->leftJoin('ws.event', 'e')
            ->addSelect('e')
            ->where('ws.event = :event')
            ->setParameter('event', $event)
            ->getQuery()
            ->getResult();
    }

    public function getDatesEvent(Event $event)
    {
        return $this->createQueryBuilder('ws')
            ->leftJoin('ws.event', 'e')
            ->addSelect('e')
            ->where('ws.event = :event')
            ->setParameter('event', $event)
            ->andWhere('ws.startAt > :dateNow')
            ->setParameter('dateNow', new \DateTime())
            ->orderBy('ws.startAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    // /**
    //  * @return WorkshopDate[] Returns an array of WorkshopDate objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('w')
            ->andWhere('w.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('w.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?WorkshopDate
    {
        return $this->createQueryBuilder('w')
            ->andWhere('w.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
