<?php

/**
 * This file is part of the photos_gallery project
 * 
 * Author: Romain Bertholon <romain.bertholon@gmail.com>
 */

namespace App\Entity;

use App\Repository\TagRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * This class is a Tag entity, related to the pictuire entity, with a one to
 * many relationship (each tag can have several pictures, and each picture
 * only one tag).
 * 
 * @ORM\Entity(repositoryClass=TagRepository::class)
 */
class Tag
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @ORM\OneToMany(targetEntity=Picture::class, mappedBy="tag")
     */
    private $pictures;

    public function __construct()
    {
        $this->pictures = new ArrayCollection();
    }

    /**
     * @return int the tag id
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return string the tag name
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Set the tag name.
     * 
     * @param string $name the name of the tag
     * 
     * @return Tag a reference to the current tag
     */
    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return Collection|Picture[]
     */
    public function getPictures(): Collection
    {
        return $this->pictures;
    }

    /**
     * Add a picture to the current tag
     * 
     * @param Picture $pictures the picture to add
     * 
     * @return Tag a reference to the current tag
     */
    public function addPicture(Picture $picture): self
    {
        if (!$this->pictures->contains($picture)) {
            $this->pictures[] = $picture;
            $picture->setTag($this);
        }

        return $this;
    }

    /**
     * Remove a picture from the current tag
     * 
     * @param Picture $picture the picture to remove
     * 
     * @return Tag a reference to the current tag
     */
    public function removePicture(Picture $picture): self
    {
        if ($this->pictures->removeElement($picture)) {
            // set the owning side to null (unless already changed)
            if ($picture->getTag() === $this) {
                $picture->setTag(null);
            }
        }

        return $this;
    }
}
