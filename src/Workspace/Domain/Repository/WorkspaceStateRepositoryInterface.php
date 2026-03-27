<?php

declare(strict_types=1);

namespace App\Workspace\Domain\Repository;

use App\Workspace\Domain\Model\WorkspaceState;

interface WorkspaceStateRepositoryInterface
{
    public function load(): WorkspaceState;

    public function save(WorkspaceState $workspaceState): void;
}
