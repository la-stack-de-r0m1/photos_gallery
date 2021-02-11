<?php

namespace App\DataFixtures;

use Symfony\Component\String\Slugger\AsciiSlugger;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use App\Entity\Picture;
use App\Entity\Tag;
use App\Entity\Comment;

class PictureFixture extends Fixture
{
    public function load(ObjectManager $manager)
    {
        $slugger = new AsciiSlugger();
        $faker = \Faker\Factory::create('en_EN');

        //create three tags
        for ($i = 0; $i < 3; $i++) {
            $tag = new Tag(); 
            $tag->setName($faker->word());
            $manager->persist($tag);

            // create between 7 and 10 pictures
            for ($j = 0; $j < mt_rand(7, 10); $j++) {
                $picture = new Picture();
                $name = $faker->sentence();              
                $safeName = $slugger->slug($picture->getName() . '-' . uniqid());
                
                $picture->setName($name)
                        ->setSlugName($safeName)
                        ->setPictureFilename($faker->imageUrl())
                        ->setAddedAt($faker->dateTime())
                        ->setTag($tag);
                $manager->persist($picture);

                // create comments for the article
                for ($k = 0; $k < mt_rand(0, 4); $k++) {
                    $comment = new Comment();

                    // generate a date between the date the picture was added and now
                    $now = new \DateTime();
                    $interval = $now->diff($picture->getAddedAt());
                    $days = $interval->days;
                    $minimum = '-' . $days . ' days'; 

                    $comment->setAuthor($faker->name())
                            ->setContent(join($faker->paragraphs(mt_rand(1, 3))))
                            ->setCreatedAt($faker->dateTimeBetween($minimum))
                            ->setPicture($picture);
                    $manager->persist($comment);
                }
            }
        }
        $manager->flush();
    }
}
