<?php

declare(strict_types=1);

namespace App\Workspace\Domain\Model;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;

#[ORM\Entity]
#[ORM\Table(name: 'workspace_users')]
final class User
{
    /**
     * @param list<string> $schedule
     */
    public function __construct(
        #[ORM\Id]
        #[ORM\Column(type: 'string', length: 32)]
        private string $id,
        #[ORM\Column(type: 'string', length: 255)]
        private string $name,
        #[ORM\Column(type: 'string', length: 255)]
        private string $team,
        #[ORM\Column(type: 'string', length: 32)]
        private string $assignedDeskId,
        /** @var list<string> */
        #[ORM\Column(type: 'json')]
        private array $schedule,
        #[ORM\Column(type: 'integer')]
        private int $vacationDaysTotal,
        #[ORM\Column(type: 'integer')]
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
