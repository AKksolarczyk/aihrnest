<?php

declare(strict_types=1);

namespace App\Workspace\Infrastructure\Persistence;

use App\Workspace\Domain\Model\RecurringDeskReservation;
use App\Workspace\Domain\Repository\RecurringDeskReservationRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineRecurringDeskReservationRepository implements RecurringDeskReservationRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function findAll(): array
    {
        /** @var list<RecurringDeskReservation> $reservations */
        $reservations = $this->entityManager->getRepository(RecurringDeskReservation::class)->findBy([], ['createdAt' => 'DESC']);

        return $reservations;
    }

    public function findAllForUser(string $userId): array
    {
        /** @var list<RecurringDeskReservation> $reservations */
        $reservations = $this->entityManager->getRepository(RecurringDeskReservation::class)->findBy(['userId' => $userId], ['createdAt' => 'DESC']);

        return $reservations;
    }

    public function add(RecurringDeskReservation $reservation): void
    {
        $this->entityManager->persist($reservation);
    }
}
