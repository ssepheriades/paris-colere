<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-admin',
    description: 'Crée le compte admin (ADMIN_EMAIL / ADMIN_PASSWORD) s’il n’existe pas',
)]
class CreateAdminCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
        #[Autowire(env: 'ADMIN_EMAIL')]
        private readonly string $adminEmail,
        #[Autowire(env: 'ADMIN_PASSWORD')]
        private readonly string $adminPassword,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if ($this->adminEmail === '' || $this->adminPassword === '') {
            $io->error('Définir ADMIN_EMAIL et ADMIN_PASSWORD dans l’environnement.');

            return Command::FAILURE;
        }

        $existing = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $this->adminEmail]);
        if ($existing instanceof User) {
            $io->success(sprintf('Compte déjà présent : %s', $this->adminEmail));

            return Command::SUCCESS;
        }

        $user = new User();
        $user->setEmail($this->adminEmail);
        $user->setRoles(['ROLE_ADMIN']);
        $user->setPassword($this->passwordHasher->hashPassword($user, $this->adminPassword));

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $io->success(sprintf('Admin créé : %s', $this->adminEmail));

        return Command::SUCCESS;
    }
}
