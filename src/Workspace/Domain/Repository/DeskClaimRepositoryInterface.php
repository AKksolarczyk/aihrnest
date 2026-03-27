<?php

declare(strict_types=1);

namespace App\Workspace\Domain\Repository;

use App\Workspace\Domain\Model\DeskClaim;
use DateTimeImmutable;

interface DeskClaimRepositoryInterface
{
    /**
     * @return list<DeskClaim>
     */
    public function findAll(): array;

    /**
     * @return list<DeskClaim>
     */
    public function findAllForUser(string $userId): array;

    /**
     * @return list<DeskClaim>
     */
    public function findAllForUserInRange(string $userId, DateTimeImmutable $startDate, DateTimeImmutable $endDate): array;

    public function findOneForUserAndDate(string $userId, DateTimeImmutable $date): ?DeskClaim;

    public function add(DeskClaim $deskClaim): void;

    public function remove(DeskClaim $deskClaim): void;
}
