<?php

/**
 * This file is part of the photos_gallery project
 * 
 * Author: Romain Bertholon <romain.bertholon@gmail.com>
 */

namespace App\Service;

use App\Service\PictureResizer;
use Imagine\Exception\Exception;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * This class is a service used to upload pictures.
 */

class PictureUploader
{
    /**
     * The max size of the pictures and thumnail when resizing them.
     */
    private const MAX_WIDTH         = 1000;
    private const MAX_HEIGHT        = 800;
    private const MAX_THUMB_WIDTH   = 350;
    private const MAX_THUMB_HEIGHT  = 150;

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
   // private $imagine;
    private $resizer;

    public function __construct($targetDirectory, SluggerInterface $slugger,
        PictureResizer $resizer)
    {
        $this->targetDirectory = $targetDirectory;
        $this->slugger = $slugger;
        $this->resizer = $resizer;
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
        // this id will be used to name the files and to create the picture's slug name
        $unique_id = uniqid();
        
        list($newFilename, $newThumbName) = $this->createNames($picture, $unique_id);
        
        $error = false;
        try {
            $picture->move($this->targetDirectory, $newFilename);
            $this->resizer
                ->open($this->abs($newFilename))
                ->resize(self::MAX_WIDTH, self::MAX_HEIGHT)
                ->thumbnail(self::MAX_THUMB_WIDTH, self::MAX_THUMB_HEIGHT)
                ->save($this->abs($newFilename), $this->abs($newThumbName));
        } catch (Exception $e) {
            $this->abort($newFilename, $newThumbName);
            $error = true;
        }

        return [
              'safePictureName' => $this->slugger->slug($picture_name . '-' . $unique_id)
            , 'newPictureName'  => $newFilename
            , 'thumbFilename'   => $newThumbName
            , 'error'           => $error
        ];
    }

    /**
     * Create unique file name for the uploaded picture and the thumbnail.
     * 
     * @param UploadedFile $picture the uploaded file data.
     * @param string $unique_id the unique id inserted in the file names.
     * 
     * @return array with the file name and the thumbname.
     */
    private function createNames(UploadedFile $picture, string $unique_id) : array {
        $originalFilename = pathinfo($picture->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename);
        $newFilename = $safeFilename . '-' . $unique_id;
        $newThumbName = $safeFilename . '-' . $unique_id . '-thumb' . '.' . $picture->guessExtension();
        $newFilename .= ('.' . $picture->guessExtension());

        return array($newFilename, $newThumbName);
    }

    /**
     * Abort the upload by removing files that were created if any.
     * 
     * @param string $filename the name of the uploaded picture
     * @param string $thumbname the name of the thumbnail
     */
    private function abort(string $filename, string $thumbname) {
        // if an error occured, undo everything
        $filesystem = new Filesystem();
        $filesystem->remove($this->abs($filename));
        $filesystem->remove($this->abs($thumbname));
    }

    /**
     * Build the absolute path based on the target directory.
     * 
     * @param string $path the path we want to "absolutize"
     * 
     * @return string the absolute path
     */
    private function abs(string $path) : string {
        return $this->targetDirectory . '/' . $path;
    }
}