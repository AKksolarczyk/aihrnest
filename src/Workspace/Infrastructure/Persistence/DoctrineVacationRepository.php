<?php

declare(strict_types=1);

namespace App\Workspace\Infrastructure\Persistence;

use App\Workspace\Domain\Model\Vacation;
use App\Workspace\Domain\Repository\VacationRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineVacationRepository implements VacationRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function findAll(): array
    {
        /** @var list<Vacation> $vacations */
        $vacations = $this->entityManager->getRepository(Vacation::class)->findBy([], ['startDate' => 'ASC']);

        return $vacations;
    }

    public function findAllForUser(string $userId): array
    {
        /** @var list<Vacation> $vacations */
        $vacations = $this->entityManager->getRepository(Vacation::class)->findBy(['userId' => $userId], ['startDate' => 'ASC']);

        return $vacations;
    }

    public function add(Vacation $vacation): void
    {
        $this->entityManager->persist($vacation);
    }
}
