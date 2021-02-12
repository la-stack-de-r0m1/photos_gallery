<?php

/**
 * This file is part of the photos_gallery project
 * 
 * Author: Romain Bertholon <romain.bertholon@gmail.com>
 */

namespace App\Service;

use Imagine\Image\Box;
use Imagine\Gd\Imagine;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

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

    /**
     * @var \Imagine\Gd\Imagine to resize the pictures
     */
    private $imagine;


    public function __construct($targetDirectory, SluggerInterface $slugger) {
        $this->targetDirectory = $targetDirectory;
        $this->slugger = $slugger;
        $this->imagine = new \Imagine\Gd\Imagine();
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
        
        $newFilename = $safeFilename . '-' . $unique_id;
        $newThumName = $safeFilename . '-' . $unique_id . '-thumb' . '.' . $picture->guessExtension();
        $newFilename .= ('.' . $picture->guessExtension());
        $picture->move($this->targetDirectory, $newFilename);


        list($iwidth, $iheight) = getimagesize($this->targetDirectory . '/' . $newFilename);
        $ratio = $iwidth / $iheight;
        $width = 1000;
        $height = 800;
        if ($width / $height > $ratio) {
            $width = $height * $ratio;
        } else {
            $height = $width / $ratio;
        }
        $resisedPicture = $this->imagine->open($this->targetDirectory . '/' . $newFilename);
        $resisedPicture->resize(new Box($width, $height))->save($this->targetDirectory . '/' . $newFilename);

        $thumbnail = $resisedPicture->thumbnail(new Box(200, 100));
        $thumbnail->save($this->targetDirectory . '/' . $newThumName);

        $safeName = $this->slugger->slug($picture_name . '-' . $unique_id);
        return [
              'safePictureName' => $safeName
            , 'newPictureName'  => $newFilename
            , 'thumbFilename'   => $newThumName
        ];
    }
}