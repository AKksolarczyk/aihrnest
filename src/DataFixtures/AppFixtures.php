<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Workspace\Domain\Model\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class AppFixtures extends Fixture
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        foreach ($this->buildUsers() as $user) {
            $manager->persist($user);
        }

        $manager->flush();
    }

    /**
     * @return list<User>
     */
    private function buildUsers(): array
    {
        $rows = [
            ['u1', 'Anna Kowalska', 'anna.kowalska@example.com', 'Product', 'pl', 'A-01', ['monday', 'tuesday', 'thursday'], ['ROLE_USER']],
            ['u2', 'Piotr Nowak', 'piotr.nowak@example.com', 'Operations', 'en', 'A-02', ['monday', 'wednesday', 'friday'], ['ROLE_USER', 'ROLE_ADMIN']],
            ['u3', 'Marta Zielinska', 'marta.zielinska@example.com', 'Sales', 'pl', 'B-01', ['tuesday', 'thursday', 'friday'], ['ROLE_USER']],
            ['u4', 'Tomasz Wisniewski', 'tomasz.wisniewski@example.com', 'Engineering', 'en', 'C-01', ['monday', 'wednesday', 'thursday'], ['ROLE_USER']],
            ['u5', 'Julia Kaczmarek', 'julia.kaczmarek@example.com', 'HR', 'pl', 'C-02', ['tuesday', 'wednesday', 'friday'], ['ROLE_USER']],
        ];

        $users = [];

        foreach ($rows as [$id, $name, $email, $team, $locale, $deskId, $schedule, $roles]) {
            $temporaryUser = User::register($id, $name, $email, $team, 'temporary-hash', $locale, $deskId, $schedule, 26, $roles);
            $hashedPassword = $this->passwordHasher->hashPassword($temporaryUser, 'password123');
            $user = User::register($id, $name, $email, $team, $hashedPassword, $locale, $deskId, $schedule, 26, $roles);
            $user->activate();
            $users[] = $user;
        }

        return $users;
    }
}
