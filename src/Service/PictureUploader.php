<?php

/**
 * This file is part of the photos_gallery project
 * 
 * Author: Romain Bertholon <romain.bertholon@gmail.com>
 */

namespace App\Service;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

/**
 * This class is a service used to upload pictures.
 */

class PictureUploader
{
    /**
     * @var string the directory where the picture files are stored.
     */
    private $targetDirectory;

    /**
     * @var SluggerInterface used to slug pictures name
     */
    private $slugger;

    public function __construct($targetDirectory, SluggerInterface $slugger) {
        $this->targetDirectory = $targetDirectory;
        $this->slugger = $slugger;
    }

    /**
     * Upload a picture.
     * 
     * @param UploadedFile $picture the picture file to upload.
     * @param string $picture_name the name the user chose for the picture.
     * 
     * @return array an array containing the slugged picture names. 
     */
    public function upload(UploadedFile $picture, string $picture_name) : array {
        $originalFilename = pathinfo($picture->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename);
        $unique_id = uniqid();
        $newFilename = $safeFilename . '-' . $unique_id . '.' . $picture->guessExtension();

        $safeName = $this->slugger->slug($picture_name . '-' . $unique_id);

        $picture->move($this->targetDirectory, $newFilename);

        return [
              'safePictureName' => $safeName
            , 'newPictureName'  => $newFilename
        ];
    }
}