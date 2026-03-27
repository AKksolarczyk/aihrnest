<?php

declare(strict_types=1);

namespace App\Workspace\Application\Command\RequestVacation;

use DateTimeImmutable;

final readonly class RequestVacationCommand
{
    public function __construct(
        public string $userId,
        public DateTimeImmutable $startDate,
        public DateTimeImmutable $endDate,
    ) {
    }
}
