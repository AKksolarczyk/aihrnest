<?php

declare(strict_types=1);

namespace App\Workspace\Domain\Repository;

use App\Workspace\Domain\Model\DeskLabel;

interface DeskLabelRepositoryInterface
{
    public function findByDeskId(string $deskId): ?DeskLabel;

    /**
     * @return array<string, DeskLabel>
     */
    public function findAllIndexedByDeskId(): array;

    public function save(DeskLabel $deskLabel): void;
}
