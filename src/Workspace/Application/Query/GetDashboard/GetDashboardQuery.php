<?php

declare(strict_types=1);

namespace App\Workspace\Application\Query\GetDashboard;

use DateTimeImmutable;

final readonly class GetDashboardQuery
{
    public function __construct(
        public DateTimeImmutable $selectedDate,
        public string $activeUserId,
    ) {
    }
}
