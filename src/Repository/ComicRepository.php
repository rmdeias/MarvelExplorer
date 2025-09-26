<?php

namespace App\Repository;

use App\Entity\Comic;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Comic>
 */
class ComicRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Comic::class);
    }

    /**
     * Top 10 comics récents, exclusions des variants/paperback/hardcover
     */
    public function findTopRecentComics(): array
    {
        $qb = $this->createQueryBuilder('c');

        $qb->where('c.date <= :today')
            ->andWhere('c.title NOT LIKE :variant')
            ->andWhere('c.title NOT LIKE :paperback')
            ->andWhere('c.title NOT LIKE :hardcover')
            ->setParameter('today', new \DateTime())
            ->setParameter('variant', '%variant%')
            ->setParameter('paperback', '%paperback%')
            ->setParameter('hardcover', '%hardcover%')
            ->orderBy('c.date', 'DESC')
            ->setMaxResults(20);

        return $qb->getQuery()->getResult();
    }

    public function searchComicsByTitle(string $title): array
    {
        $qb = $this->createQueryBuilder('c');

        $qb->where('c.title LIKE :search')
            ->andWhere('c.title NOT LIKE :variant')
            ->andWhere('c.title NOT LIKE :paperback')
            ->andWhere('c.title NOT LIKE :hardcover')
            ->setParameter('search', '%' . $title . '%')
            ->setParameter('variant', '%variant%')
            ->setParameter('paperback', '%paperback%')
            ->setParameter('hardcover', '%hardcover%')
            ->orderBy('c.title', 'ASC')
            ->setMaxResults(50);

        return $qb->getQuery()->getResult();
    }


    //    /**
    //     * @return Comic[] Returns an array of Comic objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('c.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Comic
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
