<?php

namespace App\DataFixtures;

use App\Entity\Comic;
use App\Entity\Serie;
use App\Entity\Character;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;

/**
 * This fixture updates pivot tables and foreign keys for
 * Comics ↔ Series (One-to-Many), Comics ↔ Characters (Many-to-Many),
 * and Series ↔ Characters (Many-to-Many) relationships.
 *
 * Usage:
 *  php bin/console doctrine:fixtures:load --append --group=relations
 */
class RelationsFixtures extends Fixture implements FixtureGroupInterface
{
    /**
     * Defines the fixture group to run this fixture independently.
     *
     * @return string[]
     */
    public static function getGroups(): array
    {
        return ['relations'];
    }

    /**
     * Main entry point: Updates all relationships for existing entities.
     *
     * @param ObjectManager $manager Doctrine entity manager
     * @return void
     */
    public function load(ObjectManager $manager): void
    {
        echo "\n--- Starting relationship updates ---\n";

        $this->updateComicSerieRelations($manager);
        $this->updateComicCharacterRelations($manager);
        $this->updateSerieCharacterRelations($manager);

        $manager->flush();
        echo "\n✅ All relationships updated successfully.\n";
    }

    /**
     * Updates the One-to-Many relationship between Comics and Series.
     * Finds each Comic by its marvelIdSerie and links it to the corresponding Serie entity.
     *
     * @param ObjectManager $manager Doctrine entity manager
     * @return void
     */
    private function updateComicSerieRelations(ObjectManager $manager): void
    {
        echo "\n[1/3] Linking Comics to Series...\n";
        $comics = $manager->getRepository(Comic::class)->findAll();
        foreach ($comics as $comic) {
            if ($comic->getMarvelIdSerie()) {
                $serie = $manager->getRepository(Serie::class)
                    ->findOneBy(['marvelId' => $comic->getMarvelIdSerie()]);
                if ($serie) {
                    $comic->setSerie($serie);
                    //echo "Linked Comic #{$comic->getMarvelId()} to Serie #{$serie->getMarvelId()}\n";
                }
            }
        }
    }

    /**
     * Updates the Many-to-Many relationship between Comics and Characters.
     * For each Comic, links its character IDs to existing Characters
     * or creates placeholder Characters if they don't exist.
     *
     * @param ObjectManager $manager Doctrine entity manager
     * @return void
     */
    private function updateComicCharacterRelations(ObjectManager $manager): void
    {
        echo "\n[2/3] Linking Comics to Characters...\n";
        $comics = $manager->getRepository(Comic::class)->findAll();

        foreach ($comics as $comic) {
            foreach ($comic->getMarvelIdsCharacter() as $charId) {
                $character = $manager->getRepository(Character::class)
                    ->findOneBy(['marvelId' => $charId]);
                if (!$character) {
                    $character = new Character();
                    $character->setMarvelId($charId);
                    $character->setName('Unknown ' . $charId);
                    $manager->persist($character);
                    echo "Created placeholder Character for ID {$charId}\n";
                }
                if (!$comic->getCharacters()->contains($character)) {
                    $comic->addCharacter($character);
                    //echo "Linked Comic #{$comic->getMarvelId()} to Character #{$charId}\n";
                }
            }
        }
    }

    /**
     * Updates the Many-to-Many relationship between Series and Characters.
     * For each Series, links its character IDs to existing Characters
     * or creates placeholder Characters if they don't exist.
     *
     * @param ObjectManager $manager Doctrine entity manager
     * @return void
     */
    private function updateSerieCharacterRelations(ObjectManager $manager): void
    {
        echo "\n[3/3] Linking Series to Characters...\n";
        $series = $manager->getRepository(Serie::class)->findAll();

        foreach ($series as $serie) {
            foreach ($serie->getMarvelIdsCharacter() as $charId) {
                $character = $manager->getRepository(Character::class)
                    ->findOneBy(['marvelId' => $charId]);
                if (!$character) {
                    $character = new Character();
                    $character->setMarvelId($charId);
                    $character->setName('Unknown ' . $charId);
                    $manager->persist($character);
                    echo "Created placeholder Character for ID {$charId}\n";
                }
                if (!$serie->getCharacters()->contains($character)) {
                    $serie->addCharacter($character);
                    //echo "Linked Serie #{$serie->getMarvelId()} to Character #{$charId}\n";
                }
            }
        }
    }
}
