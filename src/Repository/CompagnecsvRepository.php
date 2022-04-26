<?php

namespace App\Repository;

use App\Entity\Compagnecsv;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Compagnecsv|null find($id, $lockMode = null, $lockVersion = null)
 * @method Compagnecsv|null findOneBy(array $criteria, array $orderBy = null)
 * @method Compagnecsv[]    findAll()
 * @method Compagnecsv[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CompagnecsvRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Compagnecsv::class);
    }

    // /**
    //  * @return Compagnecsv[] Returns an array of Compagnecsv objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('c.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Compagnecsv
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
