<?php

namespace App\DataFixtures;

use App\Entity\Serie;
use App\Service\MarvelApiService;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;

class SeriesFixtures extends Fixture implements FixtureGroupInterface
{
    public static function getGroups(): array
    {
        return ['series'];
    }

    public function __construct(private MarvelApiService $marvelApi)
    {
    }

    /***** commande : php bin/console doctrine:fixtures:load --append --group=series **/
    public function load(ObjectManager $manager): void
    {
        $offset = 0;
        $limit = 25;
        $moreData = true;

        echo "--- start of import ---\n";

        while ($moreData) {
            try {
                $seriesData = $this->marvelApi->getSeries($limit, $offset);
                $count = count($seriesData);

                echo "Batch offset $offset : $count series\n";

                if ($count === 0) break;
                foreach ($seriesData as $c) {

                    $existing = $manager->getRepository(Serie::class)->findOneBy(['marvelId' => $c['marvelId']]);
                    if ($existing) continue;

                    $serie = new Serie();
                    $serie->setMarvelId($c['marvelId']);
                    $serie->setTitle($c['title']);
                    $serie->setDescription($c['description']);
                    $serie->setStartYear($c['startYear']);
                    $serie->setEndYear($c['endYear']);
                    $serie->setThumbnail($c['thumbnail']);
                    $serie->setCreators($c['creators']);
                    $serie->setMarvelIdsCharacter($c['marvelIdsCharacter']);
                    $manager->persist($serie);

                }

                $manager->flush();
                $manager->clear();

                $offset += $limit;
                $moreData = count($seriesData) > 0;
                sleep(1);

            } catch (\Throwable $e) {
                echo "Erreur : " . $e->getMessage() . "\n";
                break;
            }
        }

        echo "--- Import completed ---\n";
    }
}
