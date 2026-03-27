<?php

declare(strict_types=1);

namespace App\Workspace\Application\Command\ReleaseDeskClaim;

use DateTimeImmutable;

final readonly class ReleaseDeskClaimCommand
{
    public function __construct(
        public string $userId,
        public DateTimeImmutable $date,
    ) {
    }
}
