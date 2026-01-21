<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route(path: '/login', name: 'login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();

        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route(path: '/registration', name: 'registration')]
    public function registration(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user, [
            'is_admin' => false,  // inutile pour l'inscription mais pour rappel des conditions du formulaire
            'is_edit' => false,  // inutile pour l'inscription mais pour rappel des conditions du formulaire
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // récupère les 2 mots de passe
            $plainPassword = $form->get('password')->getData();
            $confirmPassword = $form->get('confirm_password')->getData();

            // vérifie qu'ils sont identiques si oui on les hash et sinon on renvoie un message d'érreur
            if ($plainPassword !== $confirmPassword) {
                $form->get('confirm_password')->addError(new FormError('Les mots de passe ne correspondent pas.'));
            } else {
                $hashedPassword = $passwordHasher->hashPassword($user, $plainPassword);
                $user->setPassword($hashedPassword);

                // prépare puis insère en BDD avec le ROLE_USER
                $user->setRoles(['ROLE_USER']);
                $entityManager->persist($user);
                $entityManager->flush();

                // renvoie vers artciles plutôt que user car la liste doit rester confidentielle
                return $this->redirectToRoute('login', [], Response::HTTP_SEE_OTHER);
            }
        }
        return $this->render('security/registration.html.twig',
            ['form' => $form->createView()]);
    }

    #[Route(path: '/logout', name: 'logout')]
    public function logout(): void
    {
//        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}