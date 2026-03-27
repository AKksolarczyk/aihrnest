<?php

declare(strict_types=1);

namespace App\Workspace\Domain\Repository;

use App\Workspace\Domain\Model\DeskWaitlistEntry;
use DateTimeImmutable;

interface DeskWaitlistRepositoryInterface
{
    /**
     * @return list<DeskWaitlistEntry>
     */
    public function findAll(): array;

    /**
     * @return list<DeskWaitlistEntry>
     */
    public function findAllForUser(string $userId): array;

    /**
     * @return list<DeskWaitlistEntry>
     */
    public function findWaitingForDeskAndDate(string $deskId, DateTimeImmutable $date): array;

    public function findActiveEntry(string $userId, string $deskId, DateTimeImmutable $date): ?DeskWaitlistEntry;

    public function findByClaimToken(string $claimToken): ?DeskWaitlistEntry;

    public function save(DeskWaitlistEntry $entry): void;
}
