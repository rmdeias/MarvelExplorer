<?php

namespace App\Repository;

use App\DTO\SeriesListDTO;
use App\Entity\Serie;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Serie>
 */
class SerieRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Serie::class);
    }


    /**
     * Searches series by title.
     *
     * Returns an array of SeriesListDTO objects.
     *
     * @param string $title The title string to search for
     * @return SeriesListDTO[] Array of DTOs containing marvelId, title, date, and thumbnail
     */
    public function searchSeriesByTitle(string $title): array
    {
        $qb = $this->createQueryBuilder('c');

        $qb->select('c.marvelId', 'c.title', 'c.thumbnail')
            ->where('c.title LIKE :search')
            ->setParameter('search', '%' . $title . '%')
            ->orderBy('c.title', 'ASC')
            ->setMaxResults(500);

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
