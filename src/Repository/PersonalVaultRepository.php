<?php

namespace App\Repository;

use App\Entity\PersonalVault;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PersonalVault>
 *
 * @method PersonalVault|null find($id, $lockMode = null, $lockVersion = null)
 * @method PersonalVault|null findOneBy(array $criteria, array $orderBy = null)
 * @method PersonalVault[]    findAll()
 * @method PersonalVault[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PersonalVaultRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PersonalVault::class);
    }

//    /**
//     * @return PersonalVault[] Returns an array of PersonalVault objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('p.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?PersonalVault
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
