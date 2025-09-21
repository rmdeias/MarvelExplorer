<?php

namespace App\DataFixtures;

use App\Entity\Character;
use App\Service\MarvelApiService;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;

class CharactersFixtures extends Fixture implements FixtureGroupInterface
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
        $moreData = true;

        echo "--- start of import ---\n";

        while ($moreData) {
            try {
                $charactersData = $this->marvelApi->getCharacters($limit, $offset);
                $count = count($charactersData);
                echo "Batch offset $offset : $count characters\n";

                foreach ($charactersData as $i => $c) {
                    // évite les doublons
                    $existing = $manager->getRepository(Character::class)
                        ->findOneBy(['marvelId' => $c['marvelId']]);
                    if ($existing) continue;

                    $character = new Character();
                    $character->setMarvelId($c['marvelId']);
                    $character->setName($c['name']);
                    $character->setDescription($c['description']);
                    $character->setThumbnail($c['thumbnail']);
                    $manager->persist($character);

                }

                $manager->flush();
                $manager->clear();

                $offset += $limit;
                $moreData = count($charactersData) === $limit;
                sleep(1);

            } catch (\Throwable $e) {
                echo "Erreur : " . $e->getMessage() . "\n";
                break;
            }
        }

        echo "--- Import completed ---\n";
    }
}
