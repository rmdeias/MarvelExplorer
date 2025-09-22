<?php
namespace App\DataFixtures;

use App\Entity\Comic;
use App\Entity\Serie;
use App\Entity\Character;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class CharacterRelationsFixtures extends Fixture
{
    public function __construct() {}

    public function load(ObjectManager $manager): void
    {
        echo"--- Début ajout des relations Comic↔Character  ---\n";

        // Associe les personnages aux comics
        $comics = $manager->getRepository(Comic::class)->findAll();
        foreach ($comics as $comic) {
            foreach ($comic->getMarvelIdsCharacter() as $charId) {
                $character = $manager->getRepository(Character::class)
                    ->findOneBy(['marvelId' => $charId]);

                if (!$character) {
                    $character = new Character();
                    $character->setMarvelId($charId);
                    $character->setName('Unknown' . $charId);
                    $manager->persist($character);
                }
                $comic->addCharacter($character);
            }
        }

        echo"--- Début ajout des relations Serie↔Character ---\n";

        $series = $manager->getRepository(Serie::class)->findAll();
        foreach ($series as $serie) {
            foreach ($serie->getMarvelIdsCharacter() as $charId) {
                $character = $manager->getRepository(Character::class)
                    ->findOneBy(['marvelId' => $charId]);

                if (!$character) {
                    $character = new Character();
                    $character->setMarvelId($charId);
                    $character->setName('Unknown' . $charId);
                    $manager->persist($character);
                }
                $serie->addCharacter($character);
            }
        }

        $manager->flush();
        echo"--- Relations ajoutées avec succès ---";
    }
}
