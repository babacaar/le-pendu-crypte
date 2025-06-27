<?php

namespace App\Controller\Front;

use App\Entity\Game;
use App\Entity\GameSession;
use App\Entity\User;
use App\Repository\FreeModeRepository;
use App\Repository\GameRepository;
use App\Repository\GameSessionRepository;
use App\Service\CaesarCipherService;
use App\Service\GameSessionService;
use App\Service\PointSystemService;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;

final class FreeModeController extends AbstractController
{

    #[Route('/free-mode', name: 'app_free_mode', methods: ['GET', 'POST'])]
    public function freeMode(Request $request, SessionInterface $session, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        $existingSession = $entityManager->getRepository(GameSession::class)->findOneBy([
            'user' => $user,
            'gameType' => 'freemode',
            'isComplete' => false,
        ]);
        if($existingSession) {
            $session->set('gameSessionId', $existingSession->getId());
        }

        // Handle POST request when difficulty is chosen
        if ($request->isMethod('POST')) {
            $selectedDifficulty = $request->request->get('difficulty');
            $session->set('difficulty', $selectedDifficulty);
            return $this->redirectToRoute('app_free_mode');
        }
    
        // Redirect to game if session exists
        if ($session->has('difficulty')) {
            return $this->redirectToRoute('app_game_play');
        }
       
    
        // Otherwise, show difficulty selection
        return $this->render('front/free_mode/difficulty.html.twig');
    }

    #[Route('/free-mode/change-difficulty', name: 'app_change_difficulty', methods: ['POST'])]
    public function changeDifficulty(SessionInterface $session, GameSessionRepository $gameSessionRepository): Response
    {
        // Reset session variables for Free Mode
        $session->remove('difficulty');
        
        return $this->redirectToRoute('app_free_mode');
    }

    #[Route('/free-mode/play', name: 'app_game_play')]
    public function playGame(CaesarCipherService $cipherService, FreeModeRepository $freeModeRepository, SessionInterface $session, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }


        // Reset session values related to the previous word
        $session->remove('encryptedWord');
        $session->remove('realWord');
        $session->remove('cipherShift');
        $session->remove('errorCount');
        $session->remove('revealedWord');
        $session->remove('hintUsed'); 

        
        // Check if an active session exists for this user
        $gameSession = $entityManager->getRepository(GameSession::class)->findOneBy([
            'user' => $user,
            'gameType' => 'freemode',
            'isComplete' => false,
        ]);

        if($gameSession) $session->set('gameSessionId', $gameSession->getId());
    
        $difficulty = $session->get('difficulty', 'easy'); 
        $game = $freeModeRepository->findUnusedWord($difficulty, $user);
       
        if (!$game) {
            // No words left → Move to next difficulty if possible
            if ($difficulty === 'easy') {
                $session->set('difficulty', 'medium');
            } elseif ($difficulty === 'medium') {
                $session->set('difficulty', 'hard');
            } else {
                // No more words in hard → End game
                return $this->render('front/free_mode/end.html.twig');
            }

            // Redirect to fetch a new word
            return $this->redirectToRoute('app_game_play');
        }

        if (!$gameSession) {
            // Create a new GameSession
            $gameSession = new GameSession();
            $gameSession->setUser($user);
            $gameSession->setGame($game);
            $gameSession->setGameType('freemode');
            $gameSession->setSessionStartTime(new \DateTimeImmutable());
            $gameSession->setIsComplete(false);
            $gameSession->setSessionPoints(0); // Start at 0 points

            $entityManager->persist($gameSession);
            $entityManager->flush();

            // Store session ID
            $session->set('gameSessionId', $gameSession->getId());
        }

       
        // Encrypt the word
        $realWord = strtoupper($game->getWord());
        $randomShift = rand(1, 25);
        $encryptedWord = $cipherService->encrypt($realWord, $randomShift);

        // Update session
        $session->set('encryptedWord', $encryptedWord);
        $session->set('realWord', $realWord);
        $session->set('cipherShift', $randomShift);
        $session->set('errorCount', 0);
     
        return $this->render('front/free_mode/play.html.twig', [
            'maskedWord' => str_repeat('_ ', strlen($encryptedWord)),
            'difficulty' => $difficulty,
            'errorCount' => 0,
            'hint' => $game->getHint(),
            'cipherShift'=>$randomShift,
            'sessionPoints'=>$gameSession->getSessionPoints(),
            'game' => $game,
        ]);
    }

    #[Route('/free-mode/check-letter', name: 'app_check_letter', methods: ['GET'])]
    public function checkLetter(Request $request, SessionInterface $session, CaesarCipherService $cipherService): JsonResponse
    {
        try {
            $letter = strtoupper($request->query->get('letter'));
    
            // Retrieve session data
            $encryptedWord = $session->get('encryptedWord');
            $realWord = $session->get('realWord');
            $cipherShift = $session->get('cipherShift');
            $errorCount = $session->get('errorCount', 0);
            $revealedWord = $session->get('revealedWord', str_repeat('_', strlen($encryptedWord ?? '')));
    
            // Handle missing session data
            if (!$encryptedWord || !$realWord || !$cipherShift) {
                return new JsonResponse(['success' => false, 'message' => 'Session expired'], Response::HTTP_UNAUTHORIZED);
            }
    
            // Reveal guessed letter
            $letterFound = false;
            $newRevealedWord = '';
            for ($i = 0; $i < strlen($encryptedWord); $i++) {
                if ($encryptedWord[$i] === $letter) {
                    $newRevealedWord .= $letter;
                    $letterFound = true;
                } else {
                    $newRevealedWord .= isset($revealedWord[$i]) ? $revealedWord[$i] : '_';
                }
            }
    
            if (!$letterFound) {
                $errorCount++;
                $session->set('errorCount', $errorCount);
    
                if ($errorCount >= 10) {
                    return new JsonResponse([
                        'success' => false,
                        'message' => 'Game Over!',
                        'gameOver' => true
                    ]);
                }
            } else {
                $session->set('revealedWord', $newRevealedWord);
            }
            $wordCompleted = !str_contains($newRevealedWord, '_');
            return new JsonResponse([
                'success' => true,
                'updatedWord' => implode(' ', str_split($newRevealedWord)),
                'errorCount' => $errorCount,
                'wordCompleted' => $wordCompleted, 
            ]);
        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'message' => 'Error: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/free-mode/restart', name: 'app_game_restart', methods: ['GET', 'POST'])]
    public function restartGame(SessionInterface $session,EntityManagerInterface $entityManager): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['success' => false, 'message' => 'User not logged in'], Response::HTTP_UNAUTHORIZED);
        }

        $existingSession = $entityManager->getRepository(GameSession::class)->findOneBy([
            'user' => $user,
            'gameType' => 'freemode',
            'isComplete' => false
        ]);

        if ($existingSession) {
            $existingSession->setIsComplete(true);
            $existingSession->setSessionEndTime(new \DateTimeImmutable());
            $entityManager->persist($existingSession);
        }

         // Reset session values related to the previous word
         $session->remove('difficulty');
         $session->remove('encryptedWord');
         $session->remove('realWord');
         $session->remove('cipherShift');
         $session->remove('errorCount');
         $session->remove('revealedWord');
         $entityManager->flush();

        return $this->redirectToRoute('app_game_play');
    }

    #[Route('/free-mode/decrypt-word', name: 'app_decrypt_word', methods: ['POST'])]
    public function decryptWord(Request $request, SessionInterface $session, EntityManagerInterface $entityManager,PointSystemService $pointSystemService, GameSessionRepository $gameSessionRepository): JsonResponse
    {
        try {

           /** @var User $user */
            $user = $this->getUser();
            if (!$user) {
                return new JsonResponse(['success' => false, 'message' => 'User not logged in'], Response::HTTP_UNAUTHORIZED);
            }
          
            $userGuess = strtoupper(trim($request->request->get('guess')));
            $realWord = $session->get('realWord');
            $difficulty = $session->get('difficulty', 'easy');
            $errorCount = $session->get('errorCount', 0);
            $hintUsed = $session->get('hintUsed', false);

            if (!$realWord) {
                return new JsonResponse(['success' => false, 'message' => 'Session expired'], Response::HTTP_UNAUTHORIZED);
            }
       
            // Fetch the user's PointSystem
            $pointSystem = $user->getPointSystem();
            if (!$pointSystem) {
                return new JsonResponse(['success' => false, 'message' => 'No point system found for user'], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
            
            $gameSessionId = $session->get('gameSessionId');
            if ($gameSessionId) {
                $gameSession = $gameSessionRepository->find($gameSessionId);
            }
    

            if ($userGuess === $realWord) {
                // Calculate final points
                $finalScore = $pointSystemService->calculatePoints($difficulty, $errorCount, $hintUsed);
                
                // Update the user's PointSystem
                $pointSystemService->updateUserPoints($pointSystem, $gameSession, $finalScore, 'free');
                $dbUsedWords = $gameSession->getUsedWords() ?? [];
                $dbUsedWords[] = $realWord;
                $gameSession->setUsedWords($dbUsedWords);
    
                $entityManager->persist($gameSession);
                $entityManager->flush();

                return new JsonResponse([
                    'success' => true,
                    'message' => "Correct! You earned {$finalScore} points.",
                    'pointsEarned' => $finalScore,
                    'sessionPoints' => $gameSession->getSessionPoints(),
                    'totalPoints' => $pointSystem->getFreeModePoints(),
                    'wordFound' => true
                ]);
            } else {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Incorrect! Try again.'
                ]);
            }
        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'message' => 'Error: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/free-mode/quit', name: 'app_quit_game')]
    public function quitGame(SessionInterface $session, EntityManagerInterface $entityManager, GameSessionRepository $gameSessionRepository): Response
    {
        $gameSessionId = $session->get('gameSessionId');
        if ($gameSessionId) {
            $gameSession = $gameSessionRepository->find($gameSessionId);
            if ($gameSession) {
                $gameSession->setSessionEndTime(new \DateTimeImmutable());
                $gameSession->setIsComplete(true);
                $entityManager->flush();
            }
        }
        $session->clear();     
        return $this->redirectToRoute('app_home'); 
    }

    #[Route('/free-mode/reset', name: 'app_game_reset', methods: ['POST'])]
    public function resetGameWords(EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['success' => false, 'message' => 'User not logged in'], Response::HTTP_UNAUTHORIZED);
        }

        // Trouver toutes les sessions FreeMode et effacer leurs mots utilisés
        $gameSessions = $entityManager->getRepository(GameSession::class)->findBy([
            'user' => $user,
            'gameType' => 'freemode'
        ]);

        foreach ($gameSessions as $session) {
            $session->setUsedWords([]);
            $entityManager->persist($session);
        }

        $entityManager->flush();

        return $this->redirectToRoute('app_game_restart');
    }
}
