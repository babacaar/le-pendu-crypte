<?php

namespace App\Controller\Front;

use App\Entity\User;
use App\Repository\GameSessionRepository;
use App\Repository\StoryModeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(StoryModeRepository $storyModeRepository, GameSessionRepository $gameSessionRepository): Response
    {
        $user = $this->getUser();
        $activeStorySessions = [];
        
        if ($user instanceof User) {  // ✅ Ensure $user is a valid User object
            $activeStorySessions = $gameSessionRepository->findActiveStorySessionsForUser($user);
        }
       
        $activeGameIds = array_map(fn($session) => $session->getGame()->getId(), $activeStorySessions);
        $completedSessions = $gameSessionRepository->findBy([
            'user' => $this->getUser(),
            'isComplete' => true
        ]);
     
        $completedSessionIds = array_map(fn($session) => $session->getGame()->getId(), $completedSessions);
      
        return $this->render('front/home/index.html.twig', [
            'storyModeGames' => $storyModeRepository->findAll(),
            'activeSessions' => $activeGameIds,
            'completedSessions' => $completedSessionIds,
        ]);
    }

    #[Route('/ressource', name: 'app_ressource')]
    public function ressource(): Response
    {
        return $this->render('front/ressources/index.html.twig');
    }

    #[Route('/about', name: 'app_about')]
    public function about(): Response
    {
        return $this->render('front/ressources/about.html.twig');
    }

    #[Route('/privacy-policy', name: 'app_privacy_policy')]
    public function privacyPolicy(): Response
    {
        return $this->render('front/ressources/privacy.html.twig');
    }

    #[Route('/terms-of-service', name: 'app_terms_of_service')]
    public function termsOfService(): Response
    {
        return $this->render('front/ressources/terms.html.twig');
    }

}
