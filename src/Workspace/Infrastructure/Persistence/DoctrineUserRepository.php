<?php

declare(strict_types=1);

namespace App\Workspace\Infrastructure\Persistence;

use App\Workspace\Domain\Model\User;
use App\Workspace\Domain\Repository\UserRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineUserRepository implements UserRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function findById(string $userId): ?User
    {
        return $this->entityManager->find(User::class, $userId);
    }

    public function findByEmail(string $email): ?User
    {
        /** @var ?User $user */
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => mb_strtolower($email)]);

        return $user;
    }

    public function findAllOrderedByName(): array
    {
        /** @var list<User> $users */
        $users = $this->entityManager->getRepository(User::class)->findBy([], ['name' => 'ASC']);

        return $users;
    }

    public function save(User $user): void
    {
        $this->entityManager->persist($user);
    }
}
