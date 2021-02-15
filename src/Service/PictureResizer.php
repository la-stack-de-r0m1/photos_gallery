<?php

/**
 * This file is part of the photos_gallery project
 * 
 * Author: Romain Bertholon <romain.bertholon@gmail.com>
 */

namespace App\Service;

use Imagine\Image\Box;
use Imagine\Gd\Imagine;
use Imagine\Exception\Exception;

/**
 * Resize a picture, so it fits the server requirements.
 */
class PictureResizer
{
    /**
     * @var Imagine the imagine engine used to resize pictures
     */
    private $imagine;

    /**
     * @var ImageInterface the image that is to be resized
     */
    private $resizedPicture;

    /**
     * @var ManipulatorInterface the thumbnail of the pictures, displayed on the gallery index page
     */
    private $thumbnail;

    public function __construct() {
        $this->imagine = new Imagine();
        $this->resizedPicture = null;
        $this->thumbnail = null;
    }

    /**
     * Open a picture
     * 
     * @param string $filename the path of the picture to open
     */
    public function open(string $filename) : self {
        $this->resizedPicture= $this->imagine->open($filename);

        return $this;
    }

    /**
     * Resize the picture
     * 
     * @param int $width the width of the resized picture
     * @param int $height the height of the resized picture
     */
    public function resize(int $width, int $height) : self {
        list($width, $height) = $this->computeSize($width, $height);
        $this->resizedPicture->resize(new Box($width, $height));

        return $this;
    }

    /**
     * Create a thumbnail for the picture.
     * 
     * @param int $width the width of the thumbnail
     * @param int $height the height of the thumbnail
     */
    public function thumbnail(int $width, int $height) : self {
        list($width, $height) = $this->computeSize($width, $height);
        $this->thumbnail = $this->resizedPicture->thumbnail(new Box($width, $height));
        return $this;
    }

    /**
     * Save the resized picture and the thumbnail.
     * 
     * @param string $filename the destination picture name
     * @param string $thumbName the thumb destination file name
     */
    public function save(string $filename, string $thumbName) {
        if ($this->resizedPicture)
            $this->resizedPicture->save($filename);
        if ($this->thumbnail)
            $this->thumbnail->save($thumbName);
    }

    /**
     * Compute the size of the resized picture and keeps the ratio between
     * width and height.
     * 
     * @param int width the desired width.
     * @param int height the desired height.
     */
    private function computeSize(int $width, int $height) : array {
        $iwidth = $this->resizedPicture->getSize()->getWidth();
        $iheight = $this->resizedPicture->getSize()->getHeight();

        $ratio = $iwidth / $iheight;

        if ($width / $height > $ratio)
            $width = $height * $ratio;
        else
            $height = $width / $ratio;
        
        return array($width, $height);
    }
}
