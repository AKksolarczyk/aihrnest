<?php

declare(strict_types=1);

namespace App\Workspace\Infrastructure\Persistence;

use App\Workspace\Domain\Model\DeskWaitlistEntry;
use App\Workspace\Domain\Repository\DeskWaitlistRepositoryInterface;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineDeskWaitlistRepository implements DeskWaitlistRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function findAll(): array
    {
        /** @var list<DeskWaitlistEntry> $entries */
        $entries = $this->entityManager->getRepository(DeskWaitlistEntry::class)->findBy([], ['date' => 'ASC', 'createdAt' => 'ASC']);

        return $entries;
    }

    public function findAllForUser(string $userId): array
    {
        /** @var list<DeskWaitlistEntry> $entries */
        $entries = $this->entityManager->getRepository(DeskWaitlistEntry::class)->findBy(['userId' => $userId], ['date' => 'ASC', 'createdAt' => 'ASC']);

        return $entries;
    }

    public function findWaitingForDeskAndDate(string $deskId, DateTimeImmutable $date): array
    {
        /** @var list<DeskWaitlistEntry> $entries */
        $entries = $this->entityManager->getRepository(DeskWaitlistEntry::class)->findBy([
            'deskId' => $deskId,
            'date' => $date,
        ], ['createdAt' => 'ASC']);

        $entries = array_values(array_filter(
            $entries,
            static fn (DeskWaitlistEntry $entry): bool => $entry->status() === 'waiting',
        ));

        return $entries;
    }

    public function findActiveEntry(string $userId, string $deskId, DateTimeImmutable $date): ?DeskWaitlistEntry
    {
        /** @var list<DeskWaitlistEntry> $entries */
        $entries = $this->entityManager->getRepository(DeskWaitlistEntry::class)->findBy([
            'userId' => $userId,
            'deskId' => $deskId,
            'date' => $date,
        ]);

        foreach ($entries as $entry) {
            if ($entry->isActive()) {
                return $entry;
            }
        }

        return null;
    }

    public function findByClaimToken(string $claimToken): ?DeskWaitlistEntry
    {
        /** @var ?DeskWaitlistEntry $entry */
        $entry = $this->entityManager->getRepository(DeskWaitlistEntry::class)->findOneBy([
            'claimToken' => $claimToken,
        ]);

        return $entry;
    }

    public function save(DeskWaitlistEntry $entry): void
    {
        $this->entityManager->persist($entry);
    }
}
