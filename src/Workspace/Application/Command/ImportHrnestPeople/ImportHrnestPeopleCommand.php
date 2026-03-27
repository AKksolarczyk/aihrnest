<?php

declare(strict_types=1);

namespace App\Workspace\Application\Command\ImportHrnestPeople;

final readonly class ImportHrnestPeopleCommand
{
    public function __construct(
        public bool $dryRun,
        public ?string $peoplePath,
        public ?string $deskField,
    ) {
    }
}
