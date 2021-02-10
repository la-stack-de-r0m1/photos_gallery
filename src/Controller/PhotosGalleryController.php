<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Picture;
use App\Repository\PictureRepository;

class PhotosGalleryController extends AbstractController
{
    /**
     * @Route("/photos", name="photos_gallery")
     */
    public function index(PictureRepository $repo): Response
    {
        $pictures = $repo->findAll();
        return $this->render('photos_gallery/index.html.twig', [
            'pictures' => $pictures
        ]);
    }

    /**
     * @Route("/", name="photos_home")
     */
    public function home(): Response
    {
        return $this->render('photos_gallery/home.html.twig');
    }

    /**
     * @Route("/photos/{name}", name="photos_show")
     */
    public function show(Picture $picture) : Response
    {
        return $this->render('photos_gallery/show.html.twig', [
            'picture' => $picture
        ]);
    }
}
