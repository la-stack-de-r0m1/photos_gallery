<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\String\Slugger\SluggerInterface;
use Doctrine\ORM\EntityManagerInterface;

use App\Entity\Picture;
use App\Repository\PictureRepository;
use App\Form\PictureType;

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
    * @Route("/photos/add", name="photos_add")
    */
    public function add(Request $request, EntityManagerInterface $manager, SluggerInterface $slugger) : Response
    {
      $picture = new Picture();
      $form = $this->createForm(PictureType::class, $picture);
      $form->handleRequest($request);
     
      if ($form->isSubmitted() && $form->isValid()) {
        $pictureFile = $form->get('pictureFilename')->getData();
        if ($pictureFile) {
          $originalFilename = pathinfo($pictureFile->getClientOriginalName(), PATHINFO_FILENAME);
          // this is needed to safely include the file name as part of the URL
          $safeFilename = $slugger->slug($originalFilename);
          $unique_id = uniqid();
          $newFilename = $safeFilename . '-' . $unique_id . '.' . $pictureFile->guessExtension();

          $safeName = $slugger->slug($picture->getName() . '-' . $unique_id);

          // Move the file to the directory where brochures are stored
          try {
            $pictureFile->move(
              $this->getParameter('pictures_directory'),
              $newFilename
            );
          } catch (FileException $e) {
            // ... handle exception if something happens during file upload
          }

          // updates the 'brochureFilename' property to store the PDF file name
          // instead of its contents
          $picture->setPictureFilename($newFilename);
          $picture->setSlugName($safeName);
        }
        $picture->setAddedAt(new \DateTime());

        $manager->persist($picture);
        $manager->flush();

        return $this->redirectToRoute('photos_show', [
          'slugName' => $picture->getSlugName()
        ]);
      }

      return $this->render('photos_gallery/add.html.swig', [
        'formPicture' => $form->createView()
      ]);
    }

    /**
     * @Route("/photos/{slugName}", name="photos_show")
     */
    public function show(Picture $picture) : Response
    {
        return $this->render('photos_gallery/show.html.twig', [
            'picture' => $picture
        ]);
    }

    /**
     * @Route("/photos/{slugName}/del", name="photos_del")
     */
    public function del(Picture $picture, EntityManagerInterface $manager) : Response
    {
      $manager->remove($picture);
      $manager->flush();
      return $this->redirectToRoute('photos_gallery');
    }
}
