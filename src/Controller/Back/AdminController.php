<?php

namespace App\Controller\Back;

use App\Repository\GameSessionRepository;
use App\Repository\PointSystemRepository;
use App\Repository\StoryModeRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class AdminController extends AbstractController
{
#[Route('/admin', name: 'app_admin')]
#[IsGranted('ROLE_ADMIN')]
public function index(UserRepository $userRepo, GameSessionRepository $sessionRepo, StoryModeRepository $storyRepo, PointSystemRepository $pointsRepo): Response
{
    
    $totalUsers = $userRepo->count([]);
    $recentUsers = $userRepo->findBy([], ['id' => 'DESC'], 5);

    
    $mostPlayedStoryData = $storyRepo->findMostPlayedStoryWithCount();
    $mostPlayedStory = $mostPlayedStoryData ? $mostPlayedStoryData['story'] : null;
    $playCount = $mostPlayedStoryData ? $mostPlayedStoryData['playCount'] : 0;

    
    $topPlayers = $pointsRepo->findTopPlayers(5);

    
    $activeSessions = $sessionRepo->countActiveGames();

    return $this->render('back/admin/index.html.twig', [
        'totalUsers' => $totalUsers,
        'recentUsers' => $recentUsers,
        'mostPlayedStory' => $mostPlayedStory,
        'topPlayers' => $topPlayers,
        'activeSessions' => $activeSessions,
        'playCount' => $playCount,
    ]);
}
}
