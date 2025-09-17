<?php

namespace App\DataFixtures;

use App\Entity\Serie;
use App\Entity\Comic;
use App\Entity\Character;
use App\Entity\Creator;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // 1. Characters
        $spiderman = (new Character())
            ->setName('Spider-Man')
            ->setMarvelId(1001)
            ->setThumbnail('image')
            ->setModified('2025-09-17');
        $ironMan = (new Character())
            ->setName('Iron Man')
            ->setMarvelId(1002)
            ->setThumbnail('image')
            ->setModified('2025-09-17');
        $manager->persist($spiderman);
        $manager->persist($ironMan);

// 2. Creators
        $stanLee = (new Creator())
            ->setFirstName('Stan')
            ->setLastName('Lee')
            ->setMarvelId(2001)
            ->setThumbnail('image')
            ->setRole('Createur')
            ->setModified('2025-09-17');
        $jackKirby = (new Creator())
            ->setFirstName('Jack')
            ->setLastName('Kirby')
            ->setMarvelId(2002)
            ->setThumbnail('image')
            ->setRole('Createur')
            ->setModified('2025-09-17');
        $manager->persist($stanLee);
        $manager->persist($jackKirby);

// 3. Series
        $spiderSeries = (new Serie())
            ->setTitle('Amazing Spider-Man')
            ->setMarvelId(3001)
            ->setStartYear('2025-12-12')
            ->setEndYear('2025-12-12')
            ->setThumbnail('image')
            ->addCreator($jackKirby)
            ->addCharacter($spiderman)
            ->addCharacter($ironMan);
        $manager->persist($spiderSeries);

// 4. Comics
        $comic1 = (new Comic())
            ->setTitle('Amazing Spider-Man #1')
            ->setMarvelId(4001)
            ->setSerie($spiderSeries)
            ->addCharacter($spiderman)
            ->addCreator($stanLee)
            ->setThumbnail('image')
            ->setModified('2025-09-17')
            ->addCreator($jackKirby)
            ->addVariant(5001);
        $manager->persist($comic1);

// 5. Flush
        $manager->flush();

    }
}
