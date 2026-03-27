<?php

declare(strict_types=1);

namespace App\Workspace\Domain\Repository;

use App\Workspace\Domain\Model\Vacation;

interface VacationRepositoryInterface
{
    /**
     * @return list<Vacation>
     */
    public function findAll(): array;

    /**
     * @return list<Vacation>
     */
    public function findAllForUser(string $userId): array;

    public function add(Vacation $vacation): void;
}
