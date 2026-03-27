<?php

declare(strict_types=1);

namespace App\Workspace\Application\Command\ClaimDesk;

use DateTimeImmutable;

final readonly class ClaimDeskCommand
{
    public function __construct(
        public string $userId,
        public string $deskId,
        public DateTimeImmutable $date,
    ) {
    }
}
