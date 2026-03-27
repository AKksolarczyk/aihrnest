<?php

declare(strict_types=1);

namespace App\Workspace\Domain\Repository;

use App\Workspace\Domain\Model\User;

interface UserRepositoryInterface
{
    /**
     * @return list<User>
     */
    public function findAllOrderedByName(): array;

    public function findById(string $userId): ?User;

    public function save(User $user): void;
}
