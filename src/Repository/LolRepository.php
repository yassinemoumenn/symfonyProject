<?php

namespace App\Repository;

use App\Entity\Lol;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Lol|null find($id, $lockMode = null, $lockVersion = null)
 * @method Lol|null findOneBy(array $criteria, array $orderBy = null)
 * @method Lol[]    findAll()
 * @method Lol[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LolRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Lol::class);
    }

    // /**
    //  * @return Lol[] Returns an array of Lol objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('l.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Lol
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
