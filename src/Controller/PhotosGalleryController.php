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
     * Route to the home page. Displays a link to create a user admin
     * when it does not exist.
     * 
     * @param UserRepository $userRepo used to get the admin user.
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
     * Route to the index, where all the pictuires thumbs are displayed, allowing
     * the user to open them by clicking on them.
     * 
     * @param PictureRepository $repo the picture repository used to get
     * the pictures to display .
     * @param Request $request
     * @param PaginatorInterface $paginator used to paginate if there are more
     * than 12 pictures on the page.
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
          12 // 3 lines of 4 pictures
        );

        return $this->render('photos_gallery/index.html.twig', [
            'pictures' => $pictures
        ]);
    }

    /**
     * Route to the add picture page, that displays a form to select a picture
     * and upload it upon validation. Check if the data are valid, and save them
     * if they are. Resize the picture and create the thumbnail.
     * 
     * The admin alone can access this page.
     * 
     * @param Request $request the request containing the picture that needs to
     * be saved.
     * @param EntityManagerInterface $manager the doctrine entity manager
     * @param PictureUploader $uploader the uploader service
     * name
     * 
     * @Route("/photos/add", name="photos_add")
     */
    public function add(Request $request, EntityManagerInterface $manager,
      PictureUploader $uploader) : Response
    {
      $this->denyAccessUnlessGranted('ROLE_ADMIN');

      $picture = new Picture();
      $form = $this->createForm(PictureType::class, $picture);
      $form->handleRequest($request);
     
      if ($form->isSubmitted() && $form->isValid()) {
        $pictureFile = $form->get('pictureFilename')->getData();
        
        if ($this->uploadAndRename($picture, $uploader, $pictureFile)) {
          $manager->persist($picture);
          $manager->flush();
          return $this->redirectToRoute('photos_show', [
            'slugName' => $picture->getSlugName()
          ]);
        } else {
          $this->addFlash(
            'error',
            'Could not upload the picture!'
          );

          return $this->render('photos_gallery/add.html.twig', [
            'formPicture' => $form->createView()
          ]);
        }
      }
      return $this->render('photos_gallery/add.html.twig', [
        'formPicture' => $form->createView()
      ]);
    }

    /**
     * Helper to upload and rename the picture.
     * 
     * @param Picture $picture the picture entity
     * @param PictureUploader $uploader the uploader service
     * @param $pictureFile the uploaded file data
     * 
     * @return true if everything went fine, false otherwise
     */
    private function uploadAndRename(Picture $picture, PictureUploader $uploader, $pictureFile) : bool {
      $success = false;
      if ($pictureFile) {
        $pictureInfo = $uploader->upload($pictureFile, $picture->getName());
        $picture->setPictureFilename($pictureInfo['newPictureName'])
                ->setThumbFilename($pictureInfo['thumbFilename'])
                ->setSlugName($pictureInfo['safePictureName'])
                ->setAddedAt(new \DateTime());
        $success = !$pictureInfo['error'];
      }
      return $success;
    }

    /**
     * The route to display a pictures and its comments. Also displays
     * a form to add a comment, and handle the comment form when it's submitted.
     * 
     * @param Picture $picture the picture to display.
     * @param Request $request the request used to fill the comment form.
     * @param EntityManagerInterface $manager the doctrine ORM manager.
     * @param PictureRepository $pictureRepo the picture repository, used to get previous et next pictures.
     * 
     * @Route("/photos/{slugName}", name="photos_show")
     */
    public function show(Picture $picture, Request $request,
      EntityManagerInterface $manager, PictureRepository $pictureRepo) : Response
    {
      $comment = new Comment();
      $form = $this->createForm(CommentType::class, $comment);
      $form->handleRequest($request);
      
      $nextPicture = $pictureRepo->getNextPicture($picture->getId());
      $previousPicture = $pictureRepo->getPreviousPicture($picture->getId());

      $adding = false;
      if ($form->isSubmitted() && $form->isValid()) {
        if ($this->getUser())
          $comment->setAuthor($this->getUser()->getUsername());
        $comment->setCreatedAt(new \DateTime())
                ->setPicture($picture);
        $manager->persist($comment);
        $manager->flush();

        unset($comment);
        unset($form);
        $comment = new Comment();
        $form = $this->createForm(CommentType::class, $comment);

        $this->addFlash(
          'success',
          '✅ Comment successfully added!'
        );
      }
      return $this->render('photos_gallery/show.html.twig', [
            'picture'         => $picture
          , 'formComment'     => $form->createView()
          , 'nextPicture'     => $nextPicture
          , 'previousPicture' => $previousPicture
      ]);
    }

    /**
     * The delete picture route.
     * 
     * Remove 
     * - The file
     * - The thumbnail
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
      $this->denyAccessUnlessGranted('ROLE_ADMIN');

      $filesystem = new Filesystem();
      try {
        $filesystem->remove($this->getParameter('pictures_directory') . '/' . $picture->getPictureFilename());
        $filesystem->remove($this->getParameter('pictures_directory') . '/' . $picture->getThumbFilename());
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
