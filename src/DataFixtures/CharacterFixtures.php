<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use App\Entity\Character;
use App\Service\MarvelApiService;

class CharacterFixtures extends Fixture implements FixtureGroupInterface
{
    public static function getGroups(): array
    {
        return ['characters'];
    }

    public function __construct(private MarvelApiService $marvelApi)
    {
    }

    public function load(ObjectManager $manager): void
    {

        $charactersData = $this->marvelApi->getCharacters();

        foreach ($charactersData as $c) {
            $character = new Character();
            $character->setMarvelId($c['marvelId']);
            $character->setName($c['name']);
            $character->setDescription($c['description']);
            $character->setThumbnail($c['thumbnail']);
            $manager->persist($character);
        }

        $manager->flush();
    }
}
