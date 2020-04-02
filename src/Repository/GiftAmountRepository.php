<?php

namespace App\Repository;

use App\Entity\GiftAmount;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method GiftAmount|null find($id, $lockMode = null, $lockVersion = null)
 * @method GiftAmount|null findOneBy(array $criteria, array $orderBy = null)
 * @method GiftAmount[]    findAll()
 * @method GiftAmount[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class GiftAmountRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GiftAmount::class);
    }

    // /**
    //  * @return GiftAmount[] Returns an array of GiftAmount objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('g')
            ->andWhere('g.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('g.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?GiftAmount
    {
        return $this->createQueryBuilder('g')
            ->andWhere('g.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
