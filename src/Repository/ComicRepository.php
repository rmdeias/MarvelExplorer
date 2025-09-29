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
 * Methods included:
 * - findTopRecentComics(): returns the top 20 most recent comics, excluding variants, paperbacks, and hardcovers.
 * - searchComicsByTitle(string $title): searches comics by title with the same exclusions and returns an array of ComicsListDTO objects.
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
     * Returns the top 20 most recent comics.
     *
     * Excludes variants, paperback, and hardcover editions.
     *
     * @return array<int, array> Array of comics as associative arrays with keys:
     *                           'marvelId', 'title', 'date', 'thumbnail'
     */
    public function findTopRecentComics(): array
    {
        $qb = $this->createQueryBuilder('c');

        $qb->select('c.marvelId', 'c.title', 'c.date', 'c.thumbnail', 'c.slug')
            ->where('c.date <= :today')
            ->andWhere('c.title NOT LIKE :variant')
            ->andWhere('c.title NOT LIKE :paperback')
            ->andWhere('c.title NOT LIKE :hardcover')
            ->setParameter('today', new \DateTime())
            ->setParameter('variant', '%variant%')
            ->setParameter('paperback', '%paperback%')
            ->setParameter('hardcover', '%hardcover%')
            ->orderBy('c.date', 'DESC')
            ->setMaxResults(20);

        $results = $qb->getQuery()->getResult();
        return array_map(fn($r) => new ComicsListDTO($r['marvelId'], $r['title'], $r['date'], $r['thumbnail'], $r['slug']), $results);
    }

    /**
     * Searches comics by title.
     *
     * Excludes variants, paperback, and hardcover editions.
     * Returns an array of ComicsListDTO objects.
     *
     * @param string $title The title string to search for
     * @return ComicsListDTO[] Array of DTOs containing marvelId, title, date, and thumbnail
     */
    public function searchComicsByTitle(string $title): array
    {
        $qb = $this->createQueryBuilder('c');

        $qb->select('c.marvelId', 'c.title', 'c.date', 'c.thumbnail', 'c.slug')
            ->where('c.title LIKE :search')
            ->andWhere('c.title NOT LIKE :variant')
            ->andWhere('c.title NOT LIKE :paperback')
            ->andWhere('c.title NOT LIKE :hardcover')
            ->setParameter('search', '%' . $title . '%')
            ->setParameter('variant', '%variant%')
            ->setParameter('paperback', '%paperback%')
            ->setParameter('hardcover', '%hardcover%')
            ->orderBy('c.title', 'ASC')
            ->setMaxResults(50);

        $results = $qb->getQuery()->getResult();

        return array_map(fn($r) => new ComicsListDTO($r['marvelId'], $r['title'], $r['date'], $r['thumbnail'], $r['slug']), $results);
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
