<?php

declare(strict_types=1);

namespace App\Workspace\Domain\Service;

use DateTimeImmutable;

final class BusinessDayCounter
{
    public function countBetween(DateTimeImmutable $startDate, DateTimeImmutable $endDate): int
    {
        $days = 0;
        $currentDate = $startDate;

        while ($currentDate <= $endDate) {
            if ((int) $currentDate->format('N') <= 5) {
                ++$days;
            }

            $currentDate = $currentDate->modify('+1 day');
        }

        return $days;
    }
}
