<?php

namespace App\DataFixtures;

use App\Entity\Character;
use App\Service\MarvelApiService;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;

class CharacterFixtures extends Fixture implements FixtureGroupInterface
{
    public static function getGroups(): array
    {
        return ['characters'];
    }

    public function __construct(private MarvelApiService $marvelApi)
    {
    }

    /***** commande : php bin/console doctrine:fixtures:load --append --group=characters **/
    public function load(ObjectManager $manager): void
    {
        $limit = 100;      // max Marvel API
        $offset = 0;
        $batchSize = 50;   // flush tous les 50 characters
        $moreData = true;

        while ($moreData) {
            // récupère un batch
            $charactersData = $this->marvelApi->getCharacters($limit, null, $offset);

            foreach ($charactersData as $i => $c) {
                // évite les doublons
                $existing = $manager->getRepository(Character::class)
                    ->findOneBy(['marvelId' => $c['marvelId']]);

                if (!$existing) {
                    $character = new Character();
                    $character->setMarvelId($c['marvelId']);
                    $character->setName($c['name']);
                    $character->setDescription($c['description']);
                    $character->setThumbnail($c['thumbnail']);
                    $manager->persist($character);
                }

                // flush/clear par batch
                if (($i + 1) % $batchSize === 0) {
                    $manager->flush();
                    $manager->clear();
                }
            }

            $manager->flush();
            $manager->clear();

            $offset += $limit;
            $moreData = count($charactersData) === $limit; // continue tant qu’on a un batch complet
        }
    }
}
