<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Workspace\Domain\Model\User;
use App\Workspace\Domain\Model\UserRole;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

final class AppFixtures extends Fixture
{
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
        return [
            new User('u1', 'Anna Kowalska', UserRole::Admin, 'Product', 'A-01', ['monday', 'tuesday', 'thursday'], 26, 26),
            new User('u2', 'Piotr Nowak', UserRole::User, 'Operations', 'A-02', ['monday', 'wednesday', 'friday'], 26, 26),
            new User('u3', 'Marta Zielinska', UserRole::User, 'Sales', 'B-01', ['tuesday', 'thursday', 'friday'], 26, 26),
            new User('u4', 'Tomasz Wisniewski', UserRole::User, 'Engineering', 'C-01', ['monday', 'wednesday', 'thursday'], 26, 26),
            new User('u5', 'Julia Kaczmarek', UserRole::User, 'HR', 'C-02', ['tuesday', 'wednesday', 'friday'], 26, 26),
            new User('u6', 'Kamil Lewandowski', UserRole::User, 'Finance', 'A-05', ['monday', 'tuesday', 'friday'], 26, 26),
            new User('u7', 'Oliwia Wrobel', UserRole::User, 'Support', 'A-08', ['tuesday', 'wednesday', 'thursday'], 26, 26),
            new User('u8', 'Michal Dabrowski', UserRole::User, 'Operations', 'A-11', ['monday', 'thursday', 'friday'], 26, 26),
            new User('u9', 'Natalia Sikora', UserRole::User, 'Customer Success', 'B-03', ['monday', 'wednesday', 'thursday'], 26, 26),
            new User('u10', 'Pawel Wlodarczyk', UserRole::User, 'Finance', 'B-07', ['tuesday', 'thursday', 'friday'], 26, 26),
            new User('u11', 'Karolina Maj', UserRole::User, 'Design', 'B-10', ['monday', 'tuesday', 'wednesday'], 26, 26),
            new User('u12', 'Damian Szymczak', UserRole::User, 'Engineering', 'C-05', ['monday', 'wednesday', 'friday'], 26, 26),
            new User('u13', 'Alicja Piasecka', UserRole::User, 'QA', 'C-07', ['tuesday', 'wednesday', 'thursday'], 26, 26),
            new User('u14', 'Robert Czarnecki', UserRole::User, 'Sales', 'C-10', ['monday', 'tuesday', 'friday'], 26, 26),
        ];
    }
}
