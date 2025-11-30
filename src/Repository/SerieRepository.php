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
        $qb = $this->createQueryBuilder('s');

        // Count distinct pour éviter de compter plusieurs fois une série liée à plusieurs comics
        $qb->select('COUNT(DISTINCT s.id)')
            ->innerJoin('s.comics', 'co')
            ->andWhere("co.title LIKE '%#1'");

        // Mots à exclure
        $excludeWords = [
            'variant' => '%variant%',
            'paperback' => '%paperback%',
            'hardcover' => '%hardcover%',
            'omnibus' => '%omnibus%',
            'mini' => '%mini-poster%'
        ];

        foreach ($excludeWords as $param => $value) {
            $qb->andWhere('s.title NOT LIKE :' . $param)
                ->andWhere('co.title NOT LIKE :' . $param)
                ->setParameter($param, $value);
        }

        $total = (int)$qb->getQuery()->getSingleScalarResult();

        return ['totalItems' => $total];
    }


    /**
     * Excludes variants, paperback, and hardcover editions.
     * Returns an array of SeriesListDTO objects.
     *
     * @return SeriesListDTO[] Array of DTOs containing marvelId, title, date, and thumbnail
     */
    public function findFilteredSeries(int $page, int $itemsPerPage): array
    {
        $qb = $this->createQueryBuilder('s');

        $qb->select('s.marvelId', 's.title', 's.thumbnail', 'MIN(co.thumbnail) AS cover')
            ->innerJoin('s.comics', 'co')
            ->andWhere("co.title LIKE '%#1'");

        $excludeWords = [
            'variant' => '%variant%',
            'paperback' => '%paperback%',
            'hardcover' => '%hardcover%',
            'omnibus' => '%omnibus%',
            'mini' => '%mini-poster%'
        ];

        foreach ($excludeWords as $param => $value) {
            $qb->andWhere('s.title NOT LIKE :' . $param)
                ->andWhere('co.title NOT LIKE :' . $param)
                ->setParameter($param, $value);
        }

        // Important : GROUP BY pour éviter les doublons
        $qb->groupBy('s.id');

        $qb->orderBy('s.title', 'ASC')
            ->setFirstResult(($page - 1) * $itemsPerPage)
            ->setMaxResults($itemsPerPage);

        $results = $qb->getQuery()->getResult();

        return array_map(fn($r) => new SeriesListDTO(
            $r['marvelId'],
            $r['title'],
            $r['thumbnail'],
        //$r['cover']
        ), $results);
    }


    public function findSeriesByCreatorId(int $creatorId): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = '
        SELECT
            s.marvel_id,
            s.title,
            s.thumbnail,
            MIN(c.thumbnail) AS cover
        FROM serie s
        INNER JOIN comic c ON c.serie_id = s.id
        WHERE JSON_CONTAINS(s.creators, :creator, "$")
          AND s.title NOT LIKE :variant
          AND s.title NOT LIKE :paperback
          AND s.title NOT LIKE :hardcover
          AND s.title NOT LIKE :omnibus
          AND s.title NOT LIKE :mini
          AND c.title LIKE :first
        GROUP BY s.id, s.marvel_id, s.title, s.thumbnail
        ORDER BY s.title ASC
    ';

        $stmt = $conn->prepare($sql);

        $result = $stmt->executeQuery([
            'creator' => json_encode(['marvelCreatorId' => (int)$creatorId]),
            'variant' => '%variant%',
            'paperback' => '%paperback%',
            'hardcover' => '%hardcover%',
            'omnibus' => '%omnibus%',
            'mini' => '%mini-poster%',
            'first' => '%#1'
        ]);

        $rows = $result->fetchAllAssociative();

        return array_map(fn($r) => new SeriesListDTO(
            $r['marvel_id'],
            $r['title'],
            $r['thumbnail'], // thumbnail de la série
        //$r['cover']      // fallback : thumbnail du comic
        ), $rows);
    }

    /*

    make this update manually for fix empty cover with first comic cover if you want after run all fixtures but all is already in dump.sql

    update serie s
        INNER JOIN comic co ON co.serie_id = s.id
    set s.thumbnail = co.thumbnail
    WHERE co.title LIKE '%#1'
      AND s.thumbnail = ''
      AND s.title NOT LIKE '%variant%'
      AND co.title NOT LIKE '%variant%'
      AND s.title NOT LIKE '%paperback%'
      AND co.title NOT LIKE '%paperback%'
      AND s.title NOT LIKE '%hardcover%'
      AND co.title NOT LIKE '%hardcover%'
      AND s.title NOT LIKE '%omnibus%'
      AND co.title NOT LIKE '%omnibus%'
      AND s.title NOT LIKE '%mini-poster%'
      AND co.title NOT LIKE '%mini-poster%';

     */

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
