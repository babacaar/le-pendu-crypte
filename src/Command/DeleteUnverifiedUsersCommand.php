<?php

namespace App\Command;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:delete-unverified-users',
    description: 'Supprime les utilisateurs non vérifiés après 24 heures.',
)]
class DeleteUnverifiedUsersCommand extends Command
{
    private UserRepository $userRepository;
    private EntityManagerInterface $entityManager;
    public function __construct(UserRepository $userRepository, EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->userRepository = $userRepository;
        $this->entityManager = $entityManager;
    }

    protected function configure(): void
    {
        $this
            ->addArgument('arg1', InputArgument::OPTIONAL, 'Argument description')
            ->addOption('option1', null, InputOption::VALUE_NONE, 'Option description')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $dateLimit = new \DateTimeImmutable('-24 hours');

        $unverifiedUsers = $this->userRepository->findUnverifiedUsersBefore($dateLimit);

        if (empty($unverifiedUsers)) {
            $output->writeln('Aucun utilisateur non vérifié à supprimer.');
            return Command::SUCCESS;
        }

        foreach ($unverifiedUsers as $user) {
            $output->writeln("Suppression de l'utilisateur : " . $user->getEmail());
            $this->entityManager->remove($user);
        }

        $this->entityManager->flush();
        $output->writeln('Suppression terminée.');

        return Command::SUCCESS;
    }
}
