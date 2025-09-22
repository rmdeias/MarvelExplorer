<?php

namespace App\DataFixtures;

use App\Entity\Creator;
use App\Service\MarvelApiService;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * This fixture insert datas in creator table from Marvel Api
 *
 * Usage:
 *  php bin/console doctrine:fixtures:load --append --group=creators
 */
class CreatorsFixtures extends Fixture implements FixtureGroupInterface
{
    /**
     * Defines the fixture group to run this fixture independently.
     *
     * @return string[]
     */
    public static function getGroups(): array
    {
        return ['creators'];
    }

    /**
     * @param MarvelApiService $marvelApi
     */
    public function __construct(private MarvelApiService $marvelApi)
    {
    }

    public function load(ObjectManager $manager): void
    {
        $offset = 0;
        $limit = 50;
        $moreData = true;

        echo "--- start of import ---\n";

        while ($moreData) {
            try {
                $creatorsData = $this->marvelApi->getCreators($limit, $offset);
                $count = count($creatorsData);

                echo "Batch offset $offset : $count creators\n";

                if ($count === 0) break;
                foreach ($creatorsData as $c) {

                    if (empty($c['fullName']) && empty($c['firstName']) && empty($c['lastName'])) continue;
                    $existing = $manager->getRepository(Creator::class)->findOneBy(['marvelId' => $c['marvelId']]);
                    if ($existing) continue;

                    $creator = new Creator();
                    $creator->setMarvelId($c['marvelId']);
                    $creator->setFirstName($c['firstName']);
                    $creator->setLastName($c['lastName']);
                    $creator->setFullName($c['fullName']);
                    $creator->setThumbnail($c['thumbnail']);
                    $manager->persist($creator);

                }

                $manager->flush();
                $manager->clear();

                $offset += $limit;
                $moreData = count($creatorsData) > 0;
                sleep(1);

            } catch (\Throwable $e) {
                echo "Erreur : " . $e->getMessage() . "\n";
                break;
            }
        }

        echo "--- Import completed ---\n";
    }

}
