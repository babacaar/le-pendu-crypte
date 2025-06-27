<?php

namespace App\Controller;

use App\Entity\GameSession;
use App\Entity\User;
use App\Form\ChangePasswordType;
use App\Form\UserFormType;
use App\Repository\GameSessionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class ProfileController extends AbstractController
{
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[Route('/profile', name: 'app_profile')]
    public function index(GameSessionRepository $gameSessionRepository): Response
    {
        $user = $this->getUser();
    
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }
    
        // Fetch all game sessions for the user
        $gameSessions = $gameSessionRepository->findBy(['user' => $user]);
       
        // Calculate total points for Story Mode and Free Mode
        $totalStoryModePoints = 0;
        $totalFreeModePoints = 0;
    
        foreach ($gameSessions as $session) {
            if ($session->getGameType() === 'storymode') {
                
                $totalStoryModePoints += $session->getSessionPoints();
            } elseif ($session->getGameType() === 'freemode') {
           
                $totalFreeModePoints += $session->getSessionPoints();
            }
        }
    
        return $this->render('profile/index.html.twig', [
            'user' => $user,
            'gameSessions' => $gameSessions,
            'totalStoryModePoints' => $totalStoryModePoints,
            'totalFreeModePoints' => $totalFreeModePoints,
        ]);
    }
    

    #[Route('/profile/edit', name: 'app_profile_edit')]
    public function edit(Request $request, EntityManagerInterface $entityManager): Response
    {
         /** @var User $user */
        $user = $this->getUser();

        $formProfileEdit = $this->createForm(UserFormType::class, $user);
        $formProfileEdit->handleRequest($request);

        if ($formProfileEdit->isSubmitted() && $formProfileEdit->isValid()) {
            $entityManager->flush();
            $this->addFlash("success", "Profil mis à jour !");
            return $this->redirectToRoute('app_profile');
        }

        return $this->render('profile/edit.html.twig', [
            'formProfile' => $formProfileEdit->createView(),
        ]);
    }

    #[Route('/profile/check-pseudo', name: 'app_check_pseudo', methods: ['GET'])]
    public function checkPseudo(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $pseudo = $request->query->get('pseudo');
        $existingUser = $entityManager->getRepository(User::class)->findOneBy(['pseudo' => $pseudo]);

        return new JsonResponse(['available' => !$existingUser]);
    }

    #[Route('/profile/avatar', name: 'app_profile_avatar', methods: ['GET'])]
    public function createAvatar(SessionInterface $session): Response
    {
        /** @var User user */
        $user = $this->getUser();

        if (!$user) {
            throw $this->createNotFoundException('Utilisateur non connecté.');
        }

        // Stocker l'ID utilisateur dans la session
        $session->set('avatar_user_id', $user->getId());
        return $this->render('profile/avatar.html.twig');
       
    }
    #[Route('/profile/update-avatar', name: 'app_profile_update_avatar', methods: ['POST'])]
    public function updateAvatar(EntityManagerInterface $entityManager): JsonResponse
    {
        /** @var User user */
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['success' => false, 'message' => 'Utilisateur non connecté'], 403);
        }

        // Set the avatar filename (e.g., avatar_1.png)
        $avatarFilename = "avatar_{$user->getId()}.png";
        $user->setAvatarImage($avatarFilename);
        $entityManager->persist($user);
        $entityManager->flush();

        return new JsonResponse(['success' => true]);
    }

    #[Route('/profile/change-password', name: 'app_profile_change_password')]
    public function changePassword(Request $request, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $entityManager): Response
    {
        /** @var User */
        $user = $this->getUser();
        $form = $this->createForm(ChangePasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $newPassword = $form->get('newPassword')->getData();
            $hashedPassword = $passwordHasher->hashPassword($user, $newPassword);
            $user->setPassword($hashedPassword);

            $entityManager->flush();

            $this->addFlash('success', 'Mot de passe mis à jour avec succès !');
            return $this->redirectToRoute('app_profile');
        }

        return $this->render('profile/change_password.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/profile/delete', name: 'app_profile_delete', methods: ['POST'])]
    public function deleteAccount(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
         /** @var User */
        $user = $this->getUser();
        
        if (!$user) {
            throw $this->createNotFoundException('Utilisateur non trouvé.');
        }

        // Vérification CSRF
        if (!$this->isCsrfTokenValid('delete_account', $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_profile');
        }

        // Suppression des entités liées avant de supprimer l'utilisateur
        $gameSessions = $entityManager->getRepository(GameSession::class)->findBy(['user' => $user]);
        foreach ($gameSessions as $session) {
            $entityManager->remove($session);
        }

        if ($user->getPointSystem()) {
            $entityManager->remove($user->getPointSystem());
        }

        // Suppression de l'utilisateur
        $entityManager->remove($user);
        $entityManager->flush();

        // Déconnexion automatique après suppression
        $this->container->get('security.token_storage')->setToken(null);
        $request->getSession()->invalidate();

        $this->addFlash('success', 'Votre compte a été supprimé avec succès.');
        return $this->redirectToRoute('app_home');
    }
}
