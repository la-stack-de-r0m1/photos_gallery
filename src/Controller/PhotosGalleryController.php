<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;

use Symfony\Component\String\Slugger\SluggerInterface;
use Doctrine\ORM\EntityManagerInterface;

use App\Entity\Picture;
use App\Entity\Comment;
use App\Repository\PictureRepository;
use App\Form\PictureType;
use App\Form\CommentType;

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

          $pictureFile->move(
              $this->getParameter('pictures_directory'),
              $newFilename
          );
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

      return $this->render('photos_gallery/add.html.twig', [
        'formPicture' => $form->createView()
      ]);
    }

    /**
     * @Route("/photos/{slugName}", name="photos_show")
     */
    public function show(Picture $picture, Request $request, EntityManagerInterface $manager) : Response
    {
      $comment = new Comment();
      $form = $this->createForm(CommentType::class, $comment);
      $form->handleRequest($request);
      
      if ($form->isSubmitted() && $form->isValid()) {
        $comment->setCreatedAt(new \DateTime());
        $comment->setPicture($picture);
        
        $manager->persist($comment);
        $manager->flush();
      }

      return $this->render('photos_gallery/show.html.twig', [
          'picture' => $picture,
          'formComment' => $form->createView()
      ]);
    }

    /**
     * Remove 
     * - The file
     * - The entry from the database
     * 
     * @param Picture $picture the picture to delete
     * @param EntityManagerInterface $manager doctrine manager to modify the DB.
     * 
     * @Route("/photos/{slugName}/del", name="photos_del")
     */
    public function del(Picture $picture, EntityManagerInterface $manager) : Response
    {
      $filesystem = new Filesystem();
      $filename = $picture->getPictureFilename();
      try {
        $filesystem->remove($this->getParameter('pictures_directory') . '/' . $filename);
        $manager->remove($picture);
        $manager->flush();
      } catch (IOExceptionInterface $exception) {
        return $this->redirectToRoute('photos_show', [
          'slugName'  => $picture->getSlugName()
        ]);
      }
      return $this->redirectToRoute('photos_gallery');
    }
}
