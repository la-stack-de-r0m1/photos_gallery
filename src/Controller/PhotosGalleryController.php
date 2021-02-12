<?php

/**
 * This file is part of the photos_gallery project
 * 
 * Author: Romain Bertholon <romain.bertholon@gmail.com>
 */

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Picture;
use App\Form\CommentType;
use App\Form\PictureType;
use App\Service\PictureUploader;

use App\Repository\UserRepository;

use App\Repository\PictureRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * Controller for the photos_gallery app.
 */
class PhotosGalleryController extends AbstractController
{
    /**
     * Route to the web site home page, that does pretty much nothing.
     * 
     * @Route("/", name="photos_home")
     */
    public function home(UserRepository $userRepo): Response
    {
      $users = $userRepo->findAll();
      $need_admin = true;
      foreach ($users as $user) {
          if (in_array('ROLE_ADMIN', $user->getRoles())) {
            $need_admin = false;
            break ;
          }
      }
      return $this->render('photos_gallery/home.html.twig', [
        'require_admin_registration' => $need_admin
      ]);
    }
    /**
     * Display the index, where all the pictuires thumb are displayed, allowing
     * the user to open them by clicking on them.
     * 
     * @param PictureRepository $repo the picture repository used to get
     * pictures data to display them.
     * 
     * @Route("/photos", name="photos_gallery")
     */
    public function index(PictureRepository $repo, Request $request,
      PaginatorInterface $paginator): Response
    {
        $allPictures = $repo->findAll();

        $pictures = $paginator->paginate(
          $allPictures,
          $request->query->getInt('page', 1),
          9 // 3 x 3 pictures by page
      );

        return $this->render('photos_gallery/index.html.twig', [
            'pictures' => $pictures
        ]);
    }

    /**
     * Route to the add picture page, that displays a form to select a picture
     * and upload it upon validation. Check the data are valid, and save them
     * if they are.
     * 
     * @param Request $request the request containing the picture that need to
     * be saved.
     * @param EntityManagerInterface $manager the doctrine entity manager
     * @param PictureUploader $uploader the uploader service
     * name
     * 
     * @Route("/photos/add", name="photos_add")
     */
    public function add(Request $request, EntityManagerInterface $manager, PictureUploader $uploader) : Response
    {
      $picture = new Picture();
      $form = $this->createForm(PictureType::class, $picture);
      $form->handleRequest($request);
     
      if ($form->isSubmitted() && $form->isValid()) {
        $pictureFile = $form->get('pictureFilename')->getData();
        if ($pictureFile) {
          $pictureNames = $uploader->upload($pictureFile, $picture->getName());
          $picture->setPictureFilename($pictureNames['newPictureName'])
                  ->setSlugName($pictureNames['safePictureName'])
                  ->setAddedAt(new \DateTime());;
        }
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
     * The root to display a pictures with its comments if any. Also displays
     * a form to add a comment.
     * 
     * @param Picture $picture the picture to display
     * @param Request $request the request used to fill the comment form
     * @param EntityManagerInterface $manager the doctrine ORM manager
     * 
     * @Route("/photos/{slugName}", name="photos_show")
     */
    public function show(Picture $picture, Request $request, EntityManagerInterface $manager) : Response
    {
      $comment = new Comment();
      $form = $this->createForm(CommentType::class, $comment);
      $form->handleRequest($request);
      
      if ($form->isSubmitted() && $form->isValid()) {
        $comment->setCreatedAt(new \DateTime())
                ->setPicture($picture);
        $manager->persist($comment);
        $manager->flush();
        return $this->redirectToRoute('photos_show', [
          'slugName' => $picture->getSlugName()
        ]);
      }

      return $this->render('photos_gallery/show.html.twig', [
          'picture' => $picture,
          'formComment' => $form->createView()
      ]);
    }

    /**
     * The delete picture route.
     * 
     * Remove 
     * - The file
     * - The entry from the database
     * 
     * @param Picture $picture the picture to delete
     * @param EntityManagerInterface $manager doctrine entity manager to modify
     * the DB.
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
