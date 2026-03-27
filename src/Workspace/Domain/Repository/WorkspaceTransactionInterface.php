<?php

declare(strict_types=1);

namespace App\Workspace\Domain\Repository;

interface WorkspaceTransactionInterface
{
    public function flush(): void;
}
