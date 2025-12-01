<?php

namespace App\Repository;

use App\DTO\ComicsListDTO;
use App\Entity\Comic;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Class ComicRepository
 *
 * Repository for the Comic entity.
 *
 * Provides methods to fetch comics from the database with custom queries.
 *
 * @extends ServiceEntityRepository<Comic>
 */
class ComicRepository extends ServiceEntityRepository
{
    /**
     * ComicRepository constructor.
     *
     * @param ManagerRegistry $registry Doctrine manager registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Comic::class);
    }

    /**
     * Returns the top 30 most recent comics.
     *
     * Excludes variants, paperback, and hardcover editions.
     *
     * @return array<int, array> Array of comics as associative arrays with keys:
     *                           'marvelId', 'title', 'date', 'thumbnail'
     */
    public function findTopRecentComics(): array
    {
        $qb = $this->createQueryBuilder('c');

        $qb->select('c.marvelId', 'c.title', 'c.date', 'c.thumbnail')
            ->where('c.date <= :today')
            ->andWhere('c.title NOT LIKE :variant')
            ->andWhere('c.title NOT LIKE :paperback')
            ->andWhere('c.title NOT LIKE :hardcover')
            ->setParameter('today', new \DateTime())
            ->setParameter('variant', '%variant%')
            ->setParameter('paperback', '%paperback%')
            ->setParameter('hardcover', '%hardcover%')
            ->orderBy('c.date', 'DESC')
            ->setMaxResults(30);

        $results = $qb->getQuery()->getResult();

        return array_map(fn($r) => new ComicsListDTO(
            $r['marvelId'],
            $r['title'],
            $r['date'] ?? null, // permet null si la date n’existe pas
            $r['thumbnail']
        ), $results);

    }

    /**
     * Returns the total number of comics excluding variants, paperback, hardcover and mini-poster editions.
     *
     * @return array{totalItems: int} The total number of filtered comics
     */
    public function countFilteredComics(): array
    {
        $qb = $this->createQueryBuilder('c');
        $qb->select('count(c.marvelId)')
            ->where('c.title NOT LIKE :variant')
            ->andWhere('c.title NOT LIKE :paperback')
            ->andWhere('c.title NOT LIKE :hardcover')
            ->andWhere('c.title NOT LIKE :mini')
            ->setParameter('variant', '%variant%')
            ->setParameter('paperback', '%paperback%')
            ->setParameter('hardcover', '%hardcover%')
            ->setParameter('mini', '%mini-poster%');

        $total = (int)$qb->getQuery()->getSingleScalarResult();
        return ['totalItems' => $total,];
    }

    /**
     * Excludes variants, paperback, hardcover and mini-poster editions.
     * Returns an array of ComicsListDTO objects.
     *
     * @return ComicsListDTO[] Array of DTOs containing marvelId, title, date, and thumbnail
     */
    public function findFilteredComics(int $page, int $itemsPerPage): array
    {
        $qb = $this->createQueryBuilder('c');
        $qb->select('c.marvelId', 'c.title', 'c.date', 'c.thumbnail')
            ->where('c.title NOT LIKE :variant')
            ->andWhere('c.title NOT LIKE :paperback')
            ->andWhere('c.title NOT LIKE :hardcover')
            ->andWhere('c.title NOT LIKE :mini')
            ->setParameter('variant', '%variant%')
            ->setParameter('paperback', '%paperback%')
            ->setParameter('hardcover', '%hardcover%')
            ->setParameter('mini', '%mini-poster%')
            ->orderBy('c.title', 'ASC')
            ->setFirstResult(($page - 1) * $itemsPerPage)->setMaxResults($itemsPerPage);
        $results = $qb->getQuery()->getResult();
        return array_map(fn($r) => new ComicsListDTO(
            $r['marvelId'],
            $r['title'],
            $r['date'] ?? null, // permet null si la date n’existe pas
            $r['thumbnail']
        ), $results);

    }
    // Uncommented sample methods for reference
    /*
    public function findByExampleField($value): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('c.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();
    }

    public function findOneBySomeField($value): ?Comic
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult();
    }
    */
}
