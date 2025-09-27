<?php

namespace App\DataFixtures;

use App\Entity\Comic;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;

class ComicsSlugFixtures extends Fixture implements FixtureGroupInterface
{
    public static function getGroups(): array
    {
        return ['comicsSlug'];
    }
    public function load(ObjectManager $manager): void
    {
        $slugger = new AsciiSlugger();

        // Récupérer tous les comics existants
        $repository = $manager->getRepository(Comic::class);
        $comics = $repository->findAll();

        foreach ($comics as $comic) {
            /** @var Comic $comic */
            // Génération du slug à partir du titre
            $slug = strtolower($slugger->slug($comic->getTitle()));

            $comic->setSlug($slug);
        }

        $manager->flush();
    }
}
