<?php

declare(strict_types=1);

namespace App\Workspace\Application\Command\CreateRecurringDeskReservation;

final readonly class CreateRecurringDeskReservationResult
{
    public function __construct(
        public int $createdClaims,
        public int $skippedClaims,
    ) {
    }
}
