<?php

namespace App\DataFixtures;

use App\Entity\GameSession;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use App\Entity\User;
use App\Entity\StoryMode;
use App\Entity\FreeMode;
use App\Entity\Chapter;
use App\Entity\PointSystem;
use Faker\Factory as FakerFactory;

class AppFixtures extends Fixture
{
    private $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        $faker = FakerFactory::create("fr_FR");
        // ! Admin
        $admin = new User();
        $admin->setEmail("admin@game.com");
        $admin->setPseudo("Admin");
        $admin->setLastname("Ad");
        $admin->setFirstname("Min");
        $admin->setBirthday(new \DateTimeImmutable('1990-01-01'));
        $admin->setPassword($this->passwordHasher->hashPassword($admin, "Admin123"));
        $admin->setRoles(["ROLE_ADMIN"]);

        $manager->persist($admin);

      
        $adminPointSystem = new PointSystem();
        $adminPointSystem->setUser($admin);
        $adminPointSystem->setTotalPoints(0);
        $adminPointSystem->setStoryModePoints(0);
        $adminPointSystem->setFreeModePoints(0);
        $adminPointSystem->setCreatedAt(new \DateTimeImmutable());

        $manager->persist($adminPointSystem);
        $admin->setPointSystem($adminPointSystem);

        
        // ! USERS
        $users = [];

        for ($i = 0; $i < 10; $i++) {
            $user = new User();
            $user->setEmail($faker->unique()->email());
            $user->setPseudo($faker->userName());
            $user->setLastname($faker->lastName());
            $user->setFirstname($faker->firstName());
            $user->setBirthday(\DateTimeImmutable::createFromMutable($faker->dateTimeBetween('-60 years', '-18 years')));
            $user->setPassword($this->passwordHasher->hashPassword($user, "User123"));
            $user->setRoles(["ROLE_USER"]);

            $manager->persist($user);
            $users[] = $user;
        }

        // ! POINT SYSTEM (After Users Are Persisted)
        foreach ($users as $user) {
            $pointSystem = new PointSystem();
            $pointSystem->setUser($user); 
            $pointSystem->setTotalPoints(0);
            $pointSystem->setStoryModePoints(0);
            $pointSystem->setFreeModePoints(0);
            $pointSystem->setCreatedAt(new \DateTimeImmutable());

            $user->setPointSystem($pointSystem);

            $manager->persist($pointSystem); // ✅ Persist PointSystem AFTER linking user
        }

        // ! STORY MODE GAMES
        $storyGames = [];
        for ($i = 0; $i < 5; $i++) {
            $story = new StoryMode();
            $story->setTitle("Aventure Cryptée " . ($i + 1));
            $story->setSlug("aventure-" . ($i + 1));
            $story->setDescription($faker->sentence(),100,255);
            $story->setPicture("images/story_" . ($i + 1) . ".jpeg");
            $story->setDifficultyLevel($faker->randomElement(["easy", "medium", "hard"]));
            $story->setCreatedAt(new \DateTimeImmutable());
            $story->setPublishedAt(new \DateTimeImmutable());
            $story->setCurrentChapter(1);

            $manager->persist($story);
            $storyGames[] = $story;
        }

        // ! FREE MODE GAMES
        $freeGames = [];
        for ($i = 0; $i < 5; $i++) {
            $free = new FreeMode();
            $free->setWord($faker->word());
            $free->setHint($faker->word());
            $free->setDifficultyLevel($faker->randomElement(["easy", "medium", "hard"]));
            $free->setCreatedAt(new \DateTimeImmutable());

            $manager->persist($free);
            $freeGames[] = $free;
        }

        // ! CHAPTERS
        $chapters = [];
        foreach ($storyGames as $story) {
            for ($i = 0; $i < 3; $i++) {
                $chapter = new Chapter();
                $chapter->setChapterText($faker->text(300));
                $chapter->setWordToGuess($faker->word());
                $chapter->setHint($faker->sentence(),1, 125);
                $chapter->setChapterNumber($i + 1);
                $chapter->setCreatedAt(new \DateTimeImmutable());
                $chapter->setStoryMode($story);

                $manager->persist($chapter);
                $chapters[] = $chapter;
            }
        }

        // ! GAME SESSIONS
        foreach ($users as $user) {
            for ($i = 0; $i < 2; $i++) {
                $session = new GameSession();
                $session->setUser($user);
                $session->setGameType($faker->randomElement(["freemode", "storymode"]));
                $session->setSessionPoints($faker->numberBetween(0, 100));
                $session->setSessionStartTime(new \DateTimeImmutable());
                $session->setSessionEndTime(null);
                $session->setIsComplete(false);
                $session->setUsedWords([]);
                // Assign session to a game (either StoryMode or FreeMode)
                $randomGame = $faker->randomElement(array_merge($storyGames, $freeGames));
                $session->setGame($randomGame);

                $manager->persist($session);
            }
        }

        for ($i = 0; $i < 2; $i++) {
            $adminSession = new GameSession();
            $adminSession->setUser($admin);
            $adminSession->setGameType($faker->randomElement(["freemode", "storymode"]));
            $adminSession->setSessionPoints($faker->numberBetween(0, 100));
            $adminSession->setSessionStartTime(new \DateTimeImmutable());
            $adminSession->setSessionEndTime(null);
            $adminSession->setIsComplete(false);
            $adminSession->setUsedWords([]);
        
            // Assign a random game (StoryMode or FreeMode)
            $randomGame = $faker->randomElement(array_merge($storyGames, $freeGames));
            $adminSession->setGame($randomGame);
        
            $manager->persist($adminSession);
        } 

       


        $manager->flush();
    }
}
