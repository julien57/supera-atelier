<?php

namespace App\Repository;

use App\Entity\Event;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method Event|null find($id, $lockMode = null, $lockVersion = null)
 * @method Event|null findOneBy(array $criteria, array $orderBy = null)
 * @method Event[]    findAll()
 * @method Event[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Event::class);
    }

    public function getTopEvents()
    {
        return $this->createQueryBuilder('e')
            ->leftJoin('e.workshopDates', 'work')
            ->addSelect('work')
            ->leftJoin('e.eventType', 'et')
            ->addSelect('et')
            ->leftJoin('e.photos', 'p')
            ->addSelect('p')
            ->orderBy('e.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function searchEvents(array $parameters)
    {
        $qb = $this
            ->createQueryBuilder('e')
            ->join('e.workshopDates', 'work')
            ->join('e.user', 'u')
            ->join('e.eventType', 'et')
            ->where('e.user IS NOT NULL');

        if ($parameters['city']) {
            $qb
                ->andWhere('u.city = :city')
                ->setParameter('city', $parameters['city']);
        }

        if ($parameters['date']) {
            $dateStart = new \DateTime($parameters['date']. ' 00:00:00');
            $dateEnd = new \DateTime($parameters['date']. ' 23:59:59');

            $qb
                ->andWhere('work.startAt > :dateStart')
                ->setParameter('dateStart', $dateStart)
                ->andWhere('work.startAt < :dateEnd')
                ->setParameter('dateEnd', $dateEnd);
        }

        if ($parameters['category']) {
            $qb
                ->andWhere('et.slug = :eventType')
                ->setParameter('eventType', $parameters['category']);
        }

        return $qb
            ->getQuery()
            ->getResult();
    }

    public function getEventsNotPassed()
    {
        return $this->createQueryBuilder('e')
            ->join('e.workshopDates', 'work')
            ->where('work.startAt > :now')
            ->setParameter('now', new \DateTime('now'))
            ->andWhere('e.user IS NOT NULL')
            ->orderBy('e.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function getCountEvents()
    {
        return $this->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    // /**
    //  * @return Event[] Returns an array of Event objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('e.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Event
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
