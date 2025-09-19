<?php

namespace App\DataFixtures;

use App\Entity\Comic;
use App\Service\MarvelApiService;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;

class ComicsFixtures extends Fixture implements FixtureGroupInterface
{
    public static function getGroups(): array
    {
        return ['comics'];
    }

    public function __construct(private MarvelApiService $marvelApi)
    {
    }

    public function load(ObjectManager $manager): void
    {

        $comicsData = $this->marvelApi->getComics();

        foreach ($comicsData as $c) {
            $comics = new Comic();
            $comics->setMarvelId($c['marvelId']);
            $comics->setTitle($c['title']);
            $comics->setDescription($c['description']);
            $comics->setThumbnail($c['thumbnail']);
            $comics->setThumbnail($c['dates']);
            $comics->setThumbnail($c['variants']);
            $comics->setThumbnail($c['pageCount']);
            $comics->setThumbnail($c['creators']);
            $comics->setThumbnail($c['marvelIdSerie']);
            $comics->setThumbnail($c['marvelIdsCharacter']);
            $manager->persist($comics);
        }

        $manager->flush();
    }
}

