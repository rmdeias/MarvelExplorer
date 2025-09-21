<?php

namespace App\DataFixtures;

use App\Entity\Comic;
use App\Service\MarvelApiService;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use Psr\Log\LoggerInterface;

class ComicsFixtures extends Fixture implements FixtureGroupInterface
{
    public static function getGroups(): array
    {
        return ['comics'];
    }


    public function __construct(private MarvelApiService $marvelApi)
    {
    }

    /** commande : php bin/console doctrine:fixtures:load --append --group=comics **/
    public function load(ObjectManager $manager): void
    {
        $offset = 0;
        $limit = 50;
        $moreData = true;

        echo "--- start of import ---\n";

        while ($moreData) {
            try {
                $comics = $this->marvelApi->getComics($limit, null, $offset);
                $count = count($comics);

                echo "Batch offset $offset : $count comics\n";

                if ($count === 0) break;

                foreach ($comics as $comic) {
                    $existing = $manager->getRepository(Comic::class)
                        ->findOneBy(['marvelId' => $comic['marvelId']]);

                    if ($existing) continue;

                    $comicEntity = new Comic();
                    $comicEntity->setMarvelId($comic['marvelId']);
                    $comicEntity->setTitle($comic['title']);
                    $comicEntity->setDescription($comic['description'] ?? '');
                    $comicEntity->setPageCount($comic['pageCount'] ?? 0);
                    $comicEntity->setThumbnail($comic['thumbnail'] ?? null);
                    $comicEntity->setDate($comic['dates'] ?? null);
                    $comicEntity->setVariants($comic['variants'] ?? []);
                    $comicEntity->setCreators($comic['creators'] ?? []);
                    $comicEntity->setMarvelIdSerie($comic['marvelIdSerie'] ?? 0);
                    $comicEntity->setMarvelIdsCharacter($comic['marvelIdsCharacter'] ?? []);

                    $manager->persist($comicEntity);
                }

                $manager->flush();
                $manager->clear();

                $offset += $limit;
                $moreData = $count === $limit;
                sleep(1);

            } catch (\Throwable $e) {
                echo "Erreur : " . $e->getMessage() . "\n";
                break;
            }
        }

        echo "--- Import completed ---\n";
    }
}
