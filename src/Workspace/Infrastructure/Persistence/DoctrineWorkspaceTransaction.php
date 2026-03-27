<?php

declare(strict_types=1);

namespace App\Workspace\Infrastructure\Persistence;

use App\Workspace\Domain\Repository\WorkspaceTransactionInterface;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineWorkspaceTransaction implements WorkspaceTransactionInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function flush(): void
    {
        $this->entityManager->flush();
    }
}
