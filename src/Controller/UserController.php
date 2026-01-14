<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use App\Validator\PasswordValidator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Constraints\Expression;

//#[IsGranted('ROLE_ADMIN')]
#[Route('/user')]
final class UserController extends AbstractController
{
//    #[IsGranted('ROLE_ADMIN')]
    #[Route(name: 'user_index', methods: ['GET'])]
    public function index(UserRepository $userRepository): Response
    {
        $users = $userRepository->findAll();
        return $this->render('user/index.html.twig', [
            'current_menu' => 'users',
            'users' => $users,
        ]);
    }

//    #[IsGranted('ROLE_ADMIN')]
    #[Route('/new', name: 'user_new', methods: ['GET', 'POST'])]
    public function new(Request $request, UserRepository $userRepository, UserPasswordHasherInterface $passwordHasher,
                        PasswordValidator $passwordValidator): Response
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user, [
            'is_admin' => true,  // si accessible uniquement par admin
            'is_edit' => false,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('password')->getData();
            $confirmPassword = $form->get('confirm_password')->getData();

            if ($plainPassword !== $confirmPassword) {
                $form->get('confirm_password')->addError(new FormError('Les mots de passe ne correspondent pas.'));
            } else {
                $violations = $passwordValidator->validate($plainPassword);
                if (count($violations) > 0) {
                    foreach ($violations as $violation) {
                        $form->get('password')->addError(new FormError($violation->getMessage()));
                    }
                } else {
                    $hashedPassword = $passwordHasher->hashPassword($user, $plainPassword);
                    $user->setPassword($hashedPassword);

                    $userRepository->save($user, true);
                    $this->addFlash('success', "L'utilisateur <strong>{$user->getFirstname()} {$user->getLastname()}</strong> a bien été enregistré");

                    return $this->redirectToRoute('user_index', [], Response::HTTP_SEE_OTHER);
                }
            }
        }
        return $this->render('user/new.html.twig', [
            'current_menu' => 'users',
            'user' => $user,
            'form' => $form,
        ]);
    }

//    #[IsGranted('ROLE_ADMIN')]
    #[Route('/{id}', name: 'user_show', methods: ['GET'])]
    public function show(User $user): Response
    {
        return $this->render('user/show.html.twig', [
            'current_menu' => 'users',
            'user' => $user,
        ]);
    }
//    #[IsGranted('ROLE_USER')]
    #[Route('/{id}/edit', name: 'user_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, User $user, UserRepository $userRepository, UserInterface $loggedInUser,
                         AuthorizationCheckerInterface $authChecker, UserPasswordHasherInterface $passwordHasher,
                         PasswordValidator $passwordValidator): Response
    {
        //$isAdmin = $authChecker->isGranted('ROLE_ADMIN');

        if (!$isAdmin && $user !== $loggedInUser) {
            throw $this->createAccessDeniedException('Vous ne pouvez modifier que votre propre profil.');
        }

        $originalRoles = $user->getRoles();

        $form = $this->createForm(UserType::class, $user, [
            'is_admin' => $isAdmin,
            'is_edit' => true,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('password')->getData();
            $confirmPassword = $form->get('confirm_password')->getData();

            if (!empty($plainPassword)) {
                if ($plainPassword !== $confirmPassword) {
                    $form->get('confirm_password')->addError(new FormError('Les mots de passe ne correspondent pas.'));
                } else {
                    $violations = $passwordValidator->validate($plainPassword);
                    if (count($violations) > 0) {
                        foreach ($violations as $violation) {
                            $form->get('password')->addError(new FormError($violation->getMessage()));
                        }
                    } else {
                        $user->setPassword(
                            $passwordHasher->hashPassword($user, $plainPassword)
                        );
                    }
                }
            }

            // Si le formulaire est toujours valide après les éventuelles erreurs ajoutées
            if ($form->isValid()) {
                if (!$isAdmin) {
                    $user->setRoles($originalRoles);
                }

                $userRepository->save($user, true);
                $this->addFlash('success', "L'utilisateur <strong>{$user->getFirstname()} {$user->getLastname()}</strong> a bien été modifié");

                return $this->redirectToRoute($isAdmin ? 'user_index' : 'article_index', [], Response::HTTP_SEE_OTHER);
            }
        }

        return $this->render('user/edit.html.twig', [
            'current_menu' => 'users',
            'user' => $user,
            'form' => $form,
        ]);
    }


//    #[IsGranted('ROLE_ADMIN')]
    #[Route('/{id}', name: 'user_delete', methods: ['POST'])]
    public function delete(Request $request, User $user, UserRepository $userRepository): Response
    {
        if ($this->isCsrfTokenValid('delete' . $user->getId(), $request->getPayload()->getString('_token'))) {
            $userRepository->remove($user, true);
            $this->addFlash('success', "L'utilisateur <strong>{$user->getFirstname()} {$user->getLastname()}</strong> a bien été supprimé");
        }

        return $this->redirectToRoute('user_index', [], Response::HTTP_SEE_OTHER);
    }
}