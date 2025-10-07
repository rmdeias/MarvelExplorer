<?php

namespace App\Repository;

use App\Entity\Character;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * CharacterRepository
 *
 * Repository class responsible for fetching Character entities from the database.
 * Extends Doctrine's ServiceEntityRepository to leverage common query methods.
 *
 * @extends ServiceEntityRepository<Character>
 */
class CharacterRepository extends ServiceEntityRepository
{
    /**
     * CharacterRepository constructor.
     *
     * @param ManagerRegistry $registry The Doctrine manager registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Character::class);
    }

    /**
     * Search for characters by name.
     *
     * This method performs a partial match search on the `name` field of Character entities.
     * Results are limited to 50 items and ordered alphabetically by name.
     *
     * @param string $name The search string to filter characters by their name
     *
     * @return array<int, array{marvelId: int, name: string, thumbnail: string|null}>
     *     Returns an array of associative arrays containing:
     *     - 'marvelId' : The character's Marvel ID
     *     - 'name'     : The character's name
     *     - 'thumbnail': URL or path to the character's thumbnail image
     */
    public function searchCharactersByName(string $name): array
    {
        $qb = $this->createQueryBuilder('c');

        $qb->select('c.marvelId', 'c.name', 'c.thumbnail')
            ->where('c.name LIKE :search')
            ->setParameter('search', '%' . $name . '%')
            ->orderBy('c.name', 'ASC')
            ->setMaxResults(50);

        return $qb->getQuery()->getResult();
    }

    // Uncommented example methods for reference
    /*
    /**
     * Find by example field
     *
     * @param mixed $value The value to search for
     * @return Character[] Returns an array of Character objects
     *
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

    /**
     * Find one by some field
     *
     * @param mixed $value The value to search for
     * @return Character|null
     *
    public function findOneBySomeField($value): ?Character
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult();
    }
    */
}
