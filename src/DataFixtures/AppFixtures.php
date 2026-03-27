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
            ['u1', 'Anna Kowalska', 'anna.kowalska@example.com', 'Product', 'A-01', ['monday', 'tuesday', 'thursday']],
            ['u2', 'Piotr Nowak', 'piotr.nowak@example.com', 'Operations', 'A-02', ['monday', 'wednesday', 'friday']],
            ['u3', 'Marta Zielinska', 'marta.zielinska@example.com', 'Sales', 'B-01', ['tuesday', 'thursday', 'friday']],
            ['u4', 'Tomasz Wisniewski', 'tomasz.wisniewski@example.com', 'Engineering', 'C-01', ['monday', 'wednesday', 'thursday']],
            ['u5', 'Julia Kaczmarek', 'julia.kaczmarek@example.com', 'HR', 'C-02', ['tuesday', 'wednesday', 'friday']],
        ];

        $users = [];

        foreach ($rows as [$id, $name, $email, $team, $deskId, $schedule]) {
            $temporaryUser = User::register($id, $name, $email, $team, 'temporary-hash', $deskId, $schedule);
            $hashedPassword = $this->passwordHasher->hashPassword($temporaryUser, 'password123');
            $users[] = User::register($id, $name, $email, $team, $hashedPassword, $deskId, $schedule);
        }

        return $users;
    }
}
