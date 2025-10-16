<?php

namespace App\Repository;

use App\DTO\SeriesListDTO;
use App\Entity\Serie;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Class SerieRepository
 *
 * Repository for the Serie entity.
 *
 * Provides methods to fetch series from the database with custom queries.
 *
 * @extends ServiceEntityRepository<Serie>
 */
class SerieRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Serie::class);
    }

    /**
     * Returns the total number of series excluding variants, paperback, and hardcover editions.
     *
     * @return array{totalItems: int} The total number of filtered series
     */
    public function countFilteredSeries(): array
    {
        $qb = $this->createQueryBuilder('c');
        $qb->select('count(c.marvelId)')
            ->where('c.title NOT LIKE :variant')
            ->andWhere('c.title NOT LIKE :paperback')
            ->andWhere('c.title NOT LIKE :hardcover')
            ->setParameter('variant', '%variant%')
            ->setParameter('paperback', '%paperback%')
            ->setParameter('hardcover', '%hardcover%');
        $total = (int)$qb->getQuery()->getSingleScalarResult();
        return ['totalItems' => $total,];
    }

    /**
     * Excludes variants, paperback, and hardcover editions.
     * Returns an array of SeriesListDTO objects.
     *
     * @return SeriesListDTO[] Array of DTOs containing marvelId, title, date, and thumbnail
     */
    public function findFilteredSeries(int $page, int $itemsPerPage): array
    {
        $qb = $this->createQueryBuilder('c');
        $qb->select('c.marvelId', 'c.title', 'c.thumbnail')
            ->where('c.title NOT LIKE :variant')
            ->andWhere('c.title NOT LIKE :paperback')
            ->andWhere('c.title NOT LIKE :hardcover')
            ->setParameter('variant', '%variant%')
            ->setParameter('paperback', '%paperback%')
            ->setParameter('hardcover', '%hardcover%')
            ->orderBy('c.title', 'ASC')
            ->setFirstResult(($page - 1) * $itemsPerPage)->setMaxResults($itemsPerPage);
        $results = $qb->getQuery()->getResult();
        return array_map(fn($r) => new SeriesListDTO(
            $r['marvelId'],
            $r['title'],
            $r['thumbnail']
        ), $results);

    }

    //    /**
    //     * @return Serie[] Returns an array of Serie objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('s')
    //            ->andWhere('s.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('s.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Serie
    //    {
    //        return $this->createQueryBuilder('s')
    //            ->andWhere('s.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
