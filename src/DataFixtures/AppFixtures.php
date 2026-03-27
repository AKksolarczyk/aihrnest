<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Workspace\Domain\Model\User;
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
            new User('u1', 'Anna Kowalska', 'Product', 'A-01', ['monday', 'tuesday', 'thursday'], 26, 26),
            new User('u2', 'Piotr Nowak', 'Operations', 'A-02', ['monday', 'wednesday', 'friday'], 26, 26),
            new User('u3', 'Marta Zielinska', 'Sales', 'B-01', ['tuesday', 'thursday', 'friday'], 26, 26),
            new User('u4', 'Tomasz Wisniewski', 'Engineering', 'C-01', ['monday', 'wednesday', 'thursday'], 26, 26),
            new User('u5', 'Julia Kaczmarek', 'HR', 'C-02', ['tuesday', 'wednesday', 'friday'], 26, 26),
        ];
    }
}
