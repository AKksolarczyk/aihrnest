<?php

declare(strict_types=1);

namespace App\Workspace\Domain\Repository;

use App\Workspace\Domain\Model\DeskClaim;

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

    public function add(DeskClaim $deskClaim): void;
}
