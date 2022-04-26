<?php

namespace App\Repository;

use App\Entity\Tt;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Tt|null find($id, $lockMode = null, $lockVersion = null)
 * @method Tt|null findOneBy(array $criteria, array $orderBy = null)
 * @method Tt[]    findAll()
 * @method Tt[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TtRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Tt::class);
    }

    // /**
    //  * @return Tt[] Returns an array of Tt objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('t.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Tt
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
