<?php

declare(strict_types=1);

namespace App\Workspace\Application\Command\ImportHrnestPeople;

final readonly class ImportHrnestPeopleResult
{
    /**
     * @param list<string> $warnings
     */
    public function __construct(
        public int $fetchedCount,
        public int $createdCount,
        public int $updatedCount,
        public int $deskAssignedCount,
        public array $warnings,
    ) {
    }
}
