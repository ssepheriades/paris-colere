<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
        #[Autowire(env: 'ADMIN_EMAIL')]
        private readonly string $adminEmail,
        #[Autowire(env: 'ADMIN_PASSWORD')]
        private readonly string $adminPassword,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $existing = $manager->getRepository(User::class)->findOneBy(['email' => $this->adminEmail]);
        if ($existing instanceof User) {
            return;
        }

        $user = new User();
        $user->setEmail($this->adminEmail);
        $user->setRoles(['ROLE_ADMIN']);
        $user->setPassword($this->passwordHasher->hashPassword($user, $this->adminPassword));

        $manager->persist($user);
        $manager->flush();
    }
}
