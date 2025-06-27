<?php

namespace App\Controller\Front;

use App\Entity\Chapter;
use App\Entity\Game;
use App\Entity\GameSession;
use App\Entity\PointSystem;
use App\Entity\StoryMode;
use App\Repository\ChapterRepository;
use App\Repository\GameSessionRepository;
use App\Repository\StoryModeRepository;
use App\Service\CaesarCipherService;
use App\Service\PointSystemService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;

final class StoryModeController extends AbstractController
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }
    #[Route('/story-mode/start/{slug}', name: 'app_story_start')]
    public function storyMode(GameSessionRepository $gameSessionRepository,string $slug, StoryModeRepository $storyModeRepository): Response
    {
       
        $user = $this->getUser();
        $story = $storyModeRepository->findOneBy(['slug' => $slug]);
    
        // Check if the user already has an active session for this story
        $existingSession = $gameSessionRepository->findActiveStorySessions($user, $story);

        if (!$existingSession) {
            $gameSession = new GameSession();
            $gameSession->setUser($user);
            $gameSession->setGame($story);
            $gameSession->setGameType('storymode');
            $gameSession->setSessionStartTime(new \DateTimeImmutable());
            $gameSession->setCurrentChapter(1); 
            $gameSession->setIsComplete(false);
            $gameSession->setSessionPoints(0);

            $this->entityManager->persist($gameSession);
            $this->entityManager->flush();
        }
    
        return $this->redirectToRoute('app_story_play', ['slug' => $story->getSlug()]);
    }

    #[Route('/story-mode/play/{slug}', name: 'app_story_play')]
    public function playStory(GameSessionRepository $gameSessionRepository, string $slug, StoryModeRepository $storyModeRepository, CaesarCipherService $cipherService, SessionInterface $session, EntityManagerInterface $entityManager, Request $request): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }
        $story = $storyModeRepository->findOneBy(['slug' => $slug]);

        if (!$story) {
            throw $this->createNotFoundException("Cette histoire n'existe pas.");
        }

        // Check if the user has an active session for this story
        $gameSession = $gameSessionRepository->findActiveStorySessions($user, $story);

        if (!$gameSession) {
            return $this->redirectToRoute('app_story_start', ['slug' => $slug]); // Redirect if no session
        }

        // Get the requested chapter number from URL (or default to session)
        $currentChapterNumber = $request->query->getInt('chapter', $gameSession->getCurrentChapter());
        $nextChapterNumber = $currentChapterNumber + 1;
        $chapter = $entityManager->getRepository(Chapter::class)->findOneBy([
            'storyMode' => $story,
            'chapterNumber' => $currentChapterNumber
        ]);

        if (!$chapter) {
            return $this->redirectToRoute('app_story_end', ['slug' => $slug]); // Redirect if no more chapters
        }


        // Reset session values for a fresh start
           
        $session->remove('encryptedWord');
        $session->remove('realWord');
        $session->remove('cipherShift');
        $session->remove('errorCount');
        $session->remove('revealedWord');
        $session->remove('hintUsed');
     
        // Get the word to guess
        $realWord = mb_strtoupper($chapter->getWordToGuess(), 'UTF-8');
        $randomShift = rand(1, 25); // Random shift for encryption
        $encryptedWord = $cipherService->encrypt($realWord, $randomShift);

        // Store in session
        $session->set('encryptedWord', $encryptedWord);
        $session->set('realWord', $realWord);
        $session->set('cipherShift', $randomShift);
        $session->set('errorCount', 0);
      
        return $this->render('front/story_mode/play.html.twig', [
            'story' => $story,
            'chapter' => $chapter,
            'session' => $gameSession,
            'maskedWord' => str_repeat('_ ', strlen($encryptedWord)),
            'errorCount' => 0,
            'hint' => $chapter->getHint(),
            'cipherShift' => $randomShift,
            'sessionPoints' => $gameSession->getSessionPoints(),
            'difficulty' => $story->getDifficultyLevel(),
            'nextChapterNumber'=> $nextChapterNumber,
        ]);
    }

    

    #[Route('/story-mode/check-letter/{slug}', name: 'app_story_check_letter', methods: ['GET'])]
    public function checkLetter(
        Request $request, 
        string $slug, 
        GameSessionRepository $gameSessionRepository, 
        StoryModeRepository $storyModeRepository, 
        ChapterRepository $chapterRepository, 
        SessionInterface $session
    ): JsonResponse {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['success' => false, 'message' => 'User not authenticated'], Response::HTTP_UNAUTHORIZED);
        }
    
        $story = $storyModeRepository->findOneBy(['slug' => $slug]);
        if (!$story) {
            return new JsonResponse(['success' => false, 'message' => 'Story not found'], Response::HTTP_NOT_FOUND);
        }
    
        $gameSession = $gameSessionRepository->findActiveStorySessions($user, $story);
        if (!$gameSession) {
            return new JsonResponse(['success' => false, 'message' => 'Session expired'], Response::HTTP_UNAUTHORIZED);
        }
    
        // Retrieve letter input
        $letter = strtoupper($request->query->get('letter'));
        if (empty($letter) || !preg_match('/^[A-Z]$/', $letter)) {
            return new JsonResponse(['success' => false, 'message' => 'Invalid letter input'], Response::HTTP_BAD_REQUEST);
        }
    
        // Retrieve stored session values
        $encryptedWord = $session->get('encryptedWord');
        $realWord = $session->get('realWord');
        $cipherShift = $session->get('cipherShift');
        $revealedWord = $session->get('revealedWord', str_repeat('_', strlen($encryptedWord)));
        $errorCount = $session->get('errorCount', 0);
        
        // Check if session is correctly set
        if (!$encryptedWord || !$realWord || !$cipherShift) {
            return new JsonResponse(['success' => false, 'message' => 'Session data missing'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    
        // Reveal guessed letter **in encrypted word**
        $letterFound = false;
        $newRevealedWord = '';
        for ($i = 0; $i < mb_strlen($encryptedWord, 'UTF-8'); $i++) {
            if ($encryptedWord[$i] === $letter) {
                $newRevealedWord .= $letter;
                $letterFound = true;
            } else {
                $newRevealedWord .= $revealedWord[$i];
            }
        }
    
        if (!$letterFound) {
            $errorCount++;
            $session->set('errorCount', $errorCount);
    
            // Game Over Check (10 Errors)
            if ($errorCount >= 10) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Game Over!',
                    'gameOver' => true
                ]);
            }
        }
    
        // Update session values
        $session->set('revealedWord', $newRevealedWord);
    
        // Word Completed Check
        $wordCompleted = !str_contains($newRevealedWord, '_');
    
        return new JsonResponse([
            'success' => true,
            'updatedWord' => implode(' ', str_split($newRevealedWord)),
            'errorCount' => $errorCount,
            'wordCompleted' => $wordCompleted
        ]);
    }

   #[Route('/story-mode/decrypt-word/{slug}', name: 'app_story_decrypt_word', methods: ['POST'])]
    public function decryptWord(
        Request $request, 
        string $slug, 
        GameSessionRepository $gameSessionRepository, 
        StoryModeRepository $storyModeRepository, 
        EntityManagerInterface $entityManager, 
        PointSystemService $pointSystemService, 
        SessionInterface $session,
        ChapterRepository $chapterRepository,
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['success' => false, 'message' => 'User not logged in'], Response::HTTP_UNAUTHORIZED);
        }

        $story = $storyModeRepository->findOneBy(['slug' => $slug]);
        if (!$story) {
            return new JsonResponse(['success' => false, 'message' => 'Story not found'], Response::HTTP_NOT_FOUND);
        }

        $gameSession = $gameSessionRepository->findActiveStorySessions($user, $story);
        if (!$gameSession) {
            return new JsonResponse(['success' => false, 'message' => 'Session expired'], Response::HTTP_UNAUTHORIZED);
        }

        $realWord = $session->get('realWord'); 
        if (!$realWord) {
            return new JsonResponse(['success' => false, 'message' => 'Session expired'], Response::HTTP_UNAUTHORIZED);
        }
        
        $userGuess = strtoupper(trim($request->request->get('guess')));
     

        if ($userGuess === $realWord) {
           
            $difficulty = $story->getDifficultyLevel(); 
            $errorCount = $session->get('errorCount', 0);
            $hintUsed = $session->get('hintUsed', false);

            $finalScore = $pointSystemService->calculatePoints($difficulty, $errorCount, $hintUsed);

            $pointSystem = $user->getPointSystem();
            if (!$pointSystem) {
               // Create a new PointSystem instance
                $pointSystem = new PointSystem();
                $pointSystem->setUser($user);
                $pointSystem->setStoryModePoints(0);
                $pointSystem->setCreatedAt(new \DateTimeImmutable());
                // Persist the new point system
                $entityManager->persist($pointSystem);
                $entityManager->flush();
            }

            $pointSystemService->updateUserPoints($pointSystem, $gameSession, $finalScore, 'story');

            $nextChapterNumber = $gameSession->getCurrentChapter() + 1;
            $nextChapter = $chapterRepository->findOneBy([
                'storyMode' => $story,
                'chapterNumber' => $nextChapterNumber
            ]);
            if (!$nextChapter) {
                
                $gameSession->setIsComplete(true);
                $gameSession->setSessionEndTime(new \DateTimeImmutable());
                $entityManager->persist($gameSession);
                $entityManager->flush();
    
                return new JsonResponse([
                    'success' => true,
                    'message' => "Correct! You completed the story and earned {$finalScore} points.",
                    'pointsEarned' => $finalScore,
                    'sessionPoints' => $gameSession->getSessionPoints(),
                    'totalPoints' => $pointSystem->getStoryModePoints(),
                    'storyComplete' => true, 
                    'redirectUrl' => $this->generateUrl('app_story_end', ['slug' => $slug])
                ]);
            }
            $gameSession->setCurrentChapter($nextChapterNumber);
            $entityManager->persist($gameSession);
            $entityManager->flush();

            return new JsonResponse([
                'success' => true,
                'message' => "Correct! You earned {$finalScore} points.",
                'pointsEarned' => $finalScore,
                'sessionPoints' => $gameSession->getSessionPoints(),
                'totalPoints' => $pointSystem->getStoryModePoints(),
                'wordFound' => true,
                'nextChapter' => $nextChapterNumber
            ]);
        }

        return new JsonResponse([
            'success' => false,
            'message' => 'Incorrect! Try again.'
        ]);
    }

    #[Route('/story-mode/end/{slug}', name: 'app_story_end', methods: ['GET'])]
    public function endStory(string $slug, StoryModeRepository $storyModeRepository): Response
    {
        $story = $storyModeRepository->findOneBy(['slug' => $slug]);

        if (!$story) {
            throw $this->createNotFoundException("Cette histoire n'existe pas.");
        }

        return $this->render('front/story_mode/end.html.twig', [
            'story' => $story
        ]);
    }

}
