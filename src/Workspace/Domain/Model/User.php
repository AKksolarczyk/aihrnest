<?php

declare(strict_types=1);

namespace App\Workspace\Domain\Model;

use DateTimeImmutable;
use InvalidArgumentException;

final class User
{
    /**
     * @param list<string> $schedule
     */
    public function __construct(
        private string $id,
        private string $name,
        private string $team,
        private string $assignedDeskId,
        private array $schedule,
        private int $vacationDaysTotal,
        private int $vacationDaysRemaining,
    ) {
        if ($this->id === '') {
            throw new InvalidArgumentException('User id cannot be empty.');
        }

        if ($this->assignedDeskId === '') {
            throw new InvalidArgumentException('Assigned desk id cannot be empty.');
        }

        if ($this->vacationDaysTotal < 0 || $this->vacationDaysRemaining < 0) {
            throw new InvalidArgumentException('Vacation days cannot be negative.');
        }
    }

    public function id(): string
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function team(): string
    {
        return $this->team;
    }

    public function assignedDeskId(): string
    {
        return $this->assignedDeskId;
    }

    /**
     * @return list<string>
     */
    public function schedule(): array
    {
        return $this->schedule;
    }

    public function vacationDaysTotal(): int
    {
        return $this->vacationDaysTotal;
    }

    public function vacationDaysRemaining(): int
    {
        return $this->vacationDaysRemaining;
    }

    public function isScheduledOn(DateTimeImmutable $date): bool
    {
        return in_array(strtolower($date->format('l')), $this->schedule, true);
    }

    public function consumeVacationDays(int $requestedDays): void
    {
        if ($requestedDays < 1) {
            throw new InvalidArgumentException('Requested vacation days must be greater than zero.');
        }

        if ($requestedDays > $this->vacationDaysRemaining) {
            throw new InvalidArgumentException(sprintf(
                'Brakuje dni urlopu. Wniosek wymaga %d dni roboczych, a pozostalo %d.',
                $requestedDays,
                $this->vacationDaysRemaining,
            ));
        }

        $this->vacationDaysRemaining -= $requestedDays;
    }
}
