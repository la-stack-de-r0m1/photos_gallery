<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class AdminController extends AbstractController
{
    /**
     * @Route("/admin/registration", name="admin_register")
     */
    public function registration(Request $request, EntityManagerInterface $manager,
        UserPasswordEncoderInterface $encoder, UserRepository $userRepo): Response
    {
        $users = $userRepo->findAll();
        foreach ($users as $user) {
            if (in_array('ROLE_ADMIN', $user->getRoles()))
                return $this->redirectToRoute('security_login', [
                    'error' => 'Admin user already exists.'
                ]);
        }

        $user = new User();
        $form = $this->createForm(RegistrationType::class, $user);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $hash = $encoder->encodePassword($user, $user->getPassword());
            $user->setPassword($hash)
                 ->addAdminRole();
            $manager->persist($user);
            $manager->flush(); 

            return $this->redirectToRoute('security_login', [
                'success' => "Admin user successfully created!"
            ]);
        }

        return $this->render('admin/registration.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/admin", name="admin_home")
     */
    public function index() : Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        return $this->render('admin/index.html.twig', [
            'controller_name' => 'AdminController',
        ]);
    }
}
