<?php
/**
 * This file is part of the photos_gallery project
 * 
 * Author: Romain Bertholon <romain.bertholon@gmail.com>
 */

namespace App\DataFixtures;

use Symfony\Component\String\Slugger\AsciiSlugger;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use App\Entity\Picture;
use App\Entity\Tag;
use App\Entity\Comment;

/**
 * Fixtures to create dummy data for :
 * - Pictures
 * - Comments
 * - Tags
 * 
 * Each picture is linked to one tag, and eahc comment to one picture.
 */
class PictureFixture extends Fixture
{
    /**
     * @var \Faker\Factory to create fake data 
     */
    private $faker;

    /**
     * @var ObjectManager the manager used to write fixtures in the DB
     */
    private $manager;

    /**
     * Load the fake data set in the database. It uses Faker:
     * https://fakerphp.github.io/
     * 
     * @param ObjectManager $manager the doctrine ORM manager used to persist
     * and flush the data in the underlying model.
     */
    public function load(ObjectManager $manager)
    {
        $this->faker = \Faker\Factory::create('en_EN');
        $this->manager = $manager;

        for ($i = 0; $i < 3; $i++) {
            $tag = $this->createTag();
            $manager->persist($tag);
            
            $this->loadPictures($tag);
        }
        $manager->flush();
    }

    /**
     * Create a tag with a name generated with Faker
     * 
     * @return Tag the new tag
     */
    private function createTag() : Tag {
        $tag = new Tag(); 
        $tag->setName($this->faker->word());
        return $tag;
    }

    /**
     * Load between 7 and 10 (whgy not?) fake pictures with tags and comments.
     * 
     * @param Tag $tag the tag to which fake pictures will be attached.
     */
    private function loadPictures(Tag $tag) {
        // create between 7 and 10 pictures
        $slugger = new AsciiSlugger();
        for ($j = 0; $j < mt_rand(7, 10); $j++) {
            $picture = $this->createPicture($slugger, $tag);
            $this->manager->persist($picture);
            
            $this->loadComments($picture);
        }
    }

    /**
     * Create a picture with a tag and random data from Faker.
     * 
     * @param AsciiSlugger $slugger to generate a slug name for the pictures
     * @param Tag $tag the picture tag
     * 
     * @return Picture the new picture
     */
    public function createPicture(AsciiSlugger $slugger, Tag $tag) : Picture{
        $picture = new Picture();      
        $safeName = $slugger->slug($picture->getName() . '-' . uniqid());
        
        $picture->setName($this->faker->sentence())
                ->setSlugName($safeName)
                ->setPictureFilename($this->faker->imageUrl())
                ->setAddedAt($this->faker->dateTime())
                ->setTag($tag);
        return $picture;
    }

    /**
     * Load fake comments in the DB.
     * 
     * @param Picture $picture the picture needing comments.
     */
    private function loadComments(Picture $picture) {
        for ($k = 0; $k < mt_rand(0, 4); $k++) {
            $comment = $this->createComment($picture);
            $this->manager->persist($comment);
        }
    }

    /**
     * Create a fake comment with faker.
     * 
     * @param Picture $picture the picture to which the comment will be
     * attached.
     * 
     * @return Comment the built Comment instance
     */
    private function createComment(Picture $picture) : Comment {
        $comment = new Comment();
        $minimum = '-' . $this->daysFromNow($picture->getAddedAt()) . ' days'; 
        $comment->setAuthor($this->faker->name())
                ->setContent(join($this->faker->paragraphs(mt_rand(1, 3))))
                ->setCreatedAt($this->faker->dateTimeBetween($minimum))
                ->setPicture($picture);
        return $comment;
    }

    /**
     * Helper to  generate a date between the date the picture was added and
     * now.
     * 
     * @param \DateTime $picture_date the reference date
     * 
     * @return int the number of days from now
     */
    private function daysFromNow(\DateTime $picture_date) {
        $now = new \DateTime();
        $interval = $now->diff($picture_date);
        return $interval->days;
    }
}
