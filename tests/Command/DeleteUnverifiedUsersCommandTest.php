<?php

namespace App\Tests\Command;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class DeleteUnverifiedUsersCommandTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private UserRepository $userRepository;
    
    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
        $this->userRepository = $this->entityManager->getRepository(User::class);
        
        // Clear the database of any existing users for clean testing
        $this->purgeUsers();
    }
    
    private function purgeUsers(): void
    {
        $users = $this->userRepository->findAll();
        foreach ($users as $user) {
            $this->entityManager->remove($user);
        }
        $this->entityManager->flush();
    }
    
    public function testExecute(): void
    {
        // Create a user that should be deleted (unverified + older than 24h)
        $oldUnverifiedUser = new User();
        $oldUnverifiedUser->setEmail('old-unverified@example.com');
        $oldUnverifiedUser->setFirstname('Old');
        $oldUnverifiedUser->setLastname('Unverified');
        $oldUnverifiedUser->setPseudo('old_unverified');
        $oldUnverifiedUser->setPassword('password');
        $oldUnverifiedUser->setBirthday(new \DateTime('2000-01-01'));
        $oldUnverifiedUser->setCreatedAt(new \DateTime('-25 hours'));
        $oldUnverifiedUser->setIsVerified(false);
        $oldUnverifiedUser->setRoles(['ROLE_USER']);
        
        // Create a user that should NOT be deleted (unverified but recent)
        $recentUnverifiedUser = new User();
        $recentUnverifiedUser->setEmail('recent-unverified@example.com');
        $recentUnverifiedUser->setFirstname('Recent');
        $recentUnverifiedUser->setLastname('Unverified');
        $recentUnverifiedUser->setPseudo('recent_unverified');
        $recentUnverifiedUser->setPassword('password');
        $recentUnverifiedUser->setBirthday(new \DateTime('2000-01-01'));
        $recentUnverifiedUser->setCreatedAt(new \DateTime('-12 hours'));
        $recentUnverifiedUser->setIsVerified(false);
        $recentUnverifiedUser->setRoles(['ROLE_USER']);
        
        // Create a user that should NOT be deleted (verified)
        $verifiedUser = new User();
        $verifiedUser->setEmail('verified@example.com');
        $verifiedUser->setFirstname('Verified');
        $verifiedUser->setLastname('User');
        $verifiedUser->setPseudo('verified_user');
        $verifiedUser->setPassword('password');
        $verifiedUser->setBirthday(new \DateTime('2000-01-01'));
        $verifiedUser->setCreatedAt(new \DateTime('-48 hours'));
        $verifiedUser->setIsVerified(true);
        $verifiedUser->setRoles(['ROLE_USER']);
        
        // Save users to database
        $this->entityManager->persist($oldUnverifiedUser);
        $this->entityManager->persist($recentUnverifiedUser);
        $this->entityManager->persist($verifiedUser);
        $this->entityManager->flush();
        
        // Run the command
        $application = new Application(self::$kernel);
        $command = $application->find('app:delete-unverified-users');
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);
        
        // Assertions
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString("Suppression de l'utilisateur : old-unverified@example.com", $output);
        $this->assertStringContainsString("Suppression terminée", $output);
        
        // Check that only the old unverified user was deleted
        $remainingUsers = $this->userRepository->findAll();
        $this->assertCount(2, $remainingUsers);
        $this->assertNull($this->userRepository->findOneByEmail('old-unverified@example.com'));
        $this->assertNotNull($this->userRepository->findOneByEmail('recent-unverified@example.com'));
        $this->assertNotNull($this->userRepository->findOneByEmail('verified@example.com'));
    }
    
    protected function tearDown(): void
    {
        parent::tearDown();
        $this->purgeUsers();
        $this->entityManager->close();
    }
}