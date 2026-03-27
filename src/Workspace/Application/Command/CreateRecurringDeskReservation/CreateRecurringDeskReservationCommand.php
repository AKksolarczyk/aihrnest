<?php

declare(strict_types=1);

namespace App\Workspace\Application\Command\CreateRecurringDeskReservation;

use DateTimeImmutable;

final readonly class CreateRecurringDeskReservationCommand
{
    /**
     * @param list<string> $weekdays
     */
    public function __construct(
        public string $userId,
        public string $deskId,
        public DateTimeImmutable $startDate,
        public DateTimeImmutable $endDate,
        public array $weekdays,
    ) {
    }
}
