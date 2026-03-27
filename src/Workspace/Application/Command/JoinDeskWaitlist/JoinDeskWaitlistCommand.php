<?php

declare(strict_types=1);

namespace App\Workspace\Application\Command\JoinDeskWaitlist;

use DateTimeImmutable;

final readonly class JoinDeskWaitlistCommand
{
    public function __construct(
        public string $userId,
        public string $deskId,
        public DateTimeImmutable $date,
    ) {
    }
}
