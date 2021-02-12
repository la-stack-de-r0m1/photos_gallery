<?php

/**
 * This file is part of the photos_gallery project
 * 
 * Author: Romain Bertholon <romain.bertholon@gmail.com>
 */

namespace App\Entity;

use App\Repository\PictureRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Reprensent a picture in the DB. It actually stores two pictures (i.e. two files on the disk).
 * - one for the picture itself
 * - the other for the thumbnail
 * 
 * Note that the picture is resized to 1000 px maximum after the upload. The thumb is 350x150.
 * 
 * @ORM\Entity(repositoryClass=PictureRepository::class)
 */
class Picture
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var string the display name
     * 
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @var string the file name of the picture (i.e. public/uploads/pictures/123456.jpg)
     * It is slugged and unique.
     * 
     * @ORM\Column(type="string", length=255)
     */
    private $pictureFilename;

    /**
     * @var string The thumb file name. It's the same as pictureFilename, but with "-thum" added
     * before the extension. (i.e.  public/uploads/pictures/123456-thumb.jpg)
     * 
     * @ORM\Column(type="string", length=255)
     */
    private $thumbFilename;

    /**
     * @var string description 
     * 
     * @ORM\Column(type="string", nullable=true)
     */
    private $description;

    /**
     * @var DateTime the date and hour the picture was uploaded
     * 
     * @ORM\Column(type="datetime")
     */
    private $addedAt;

    /**
     * @var string the slugged name of the picture, based on the name.
     * 
     * @ORM\Column[type="string"]
     */
    private $slugName;

    /**
     * Each pictuires has 0 or 1 tag.
     * 
     * @ORM\ManyToOne(targetEntity=Tag::class, inversedBy="pictures")
     */
    private $tag;

    /**
     * Each picture has 0, 1 or many comments.
     * 
     * @ORM\OneToMany(targetEntity=Comment::class, mappedBy="picture", orphanRemoval=true)
     */
    private $comments;

    public function __construct()
    {
        $this->comments = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getPictureFilename(): ?string
    {
        return $this->pictureFilename;
    }

    public function setPictureFilename(string $pictureFilename): self
    {
        $this->pictureFilename = $pictureFilename;

        return $this;
    }
    
    public function getThumbFilename(): ?string
    {
        return $this->thumbFilename;
    }

    public function setThumbFilename(string $thumbFilename): self
    {
        $this->thumbFilename = $thumbFilename;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getAddedAt(): ?\DateTimeInterface
    {
        return $this->addedAt;
    }

    public function setAddedAt(\DateTimeInterface $addedAt): self
    {
        $this->addedAt = $addedAt;

        return $this;
    }

    public function getSlugName(): ?string
    {
        return $this->slugName;
    }

    public function setSlugName(string $slugName): self
    {
        $this->slugName = $slugName;

        return $this;
    }

    public function getTag(): ?Tag
    {
        return $this->tag;
    }

    public function setTag(?Tag $tag): self
    {
        $this->tag = $tag;

        return $this;
    }

    /**
     * @return Collection|Comment[]
     */
    public function getComments(): Collection
    {
        return $this->comments;
    }

    public function addComment(Comment $comment): self
    {
        if (!$this->comments->contains($comment)) {
            $this->comments[] = $comment;
            $comment->setPicture($this);
        }

        return $this;
    }

    public function removeComment(Comment $comment): self
    {
        if ($this->comments->removeElement($comment)) {
            // set the owning side to null (unless already changed)
            if ($comment->getPicture() === $this) {
                $comment->setPicture(null);
            }
        }

        return $this;
    }
}
