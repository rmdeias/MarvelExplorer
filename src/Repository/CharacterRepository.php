<?php

namespace App\Repository;

use App\DTO\CharactersListDTO;
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
     * Returns an array of CharactersListDTO objects.
     *
     * @return CharactersListDTO[] Array of DTOs containing marvelId, name, and thumbnail
     */
    public function findDTOCharacters(int $page, int $itemsPerPage): array
    {
        $qb = $this->createQueryBuilder('c');
        $qb->select('c.marvelId', 'c.name', 'c.thumbnail')
            ->setFirstResult(($page - 1) * $itemsPerPage)->setMaxResults($itemsPerPage);
        $results = $qb->getQuery()->getResult();
        return array_map(fn($r) => new CharactersListDTO(
            $r['marvelId'],
            $r['name'],
            $r['thumbnail']
        ), $results);

    }


    /**
     * Returns the total number of characters.
     *
     * @return array{totalItems: int} The total number of filtered characters
     */
    public function countCharacters(): array
    {
        $qb = $this->createQueryBuilder('c');
        $qb->select('count(c.marvelId)');
        $total = (int)$qb->getQuery()->getSingleScalarResult();
        return ['totalItems' => $total,];
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
