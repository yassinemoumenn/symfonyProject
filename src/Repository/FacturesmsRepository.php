<?php

namespace App\Repository;

use App\Entity\Facturesms;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Facturesms|null find($id, $lockMode = null, $lockVersion = null)
 * @method Facturesms|null findOneBy(array $criteria, array $orderBy = null)
 * @method Facturesms[]    findAll()
 * @method Facturesms[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FacturesmsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Facturesms::class);
    }

    // /**
    //  * @return Facturesms[] Returns an array of Facturesms objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('f.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Facturesms
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
