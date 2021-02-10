<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use App\Entity\Picture;

class PictureFixture extends Fixture
{
    public function load(ObjectManager $manager)
    {
        for ($i = 0; $i < 50; $i++) {
            $picture = new Picture();
            $picture->setName("Picture n°$i")
                    ->setPictureFilename("http://placehold.it/800x600")
                    ->setAddedAt(new \DateTime());
            $manager->persist($picture);
        }
        $manager->flush();
    }
}
