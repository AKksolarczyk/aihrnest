<?php

declare(strict_types=1);

namespace App\Workspace\Domain\Model;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;

#[ORM\Entity]
#[ORM\Table(name: 'workspace_recurring_desk_reservations')]
final class RecurringDeskReservation
{
    /**
     * @param list<string> $weekdays
     */
    public function __construct(
        #[ORM\Id]
        #[ORM\Column(type: 'string', length: 32)]
        private string $id,
        #[ORM\Column(type: 'string', length: 32)]
        private string $userId,
        #[ORM\Column(type: 'string', length: 32)]
        private string $deskId,
        #[ORM\Column(type: 'datetime_immutable')]
        private DateTimeImmutable $startDate,
        #[ORM\Column(type: 'datetime_immutable')]
        private DateTimeImmutable $endDate,
        #[ORM\Column(type: 'json')]
        private array $weekdays,
        #[ORM\Column(type: 'boolean')]
        private bool $active,
        #[ORM\Column(type: 'datetime_immutable')]
        private DateTimeImmutable $createdAt,
    ) {
        if ($this->id === '' || $this->userId === '' || $this->deskId === '') {
            throw new InvalidArgumentException('Recurring reservation identifiers cannot be empty.');
        }

        if ($this->endDate < $this->startDate) {
            throw new InvalidArgumentException('Recurring reservation end date cannot be earlier than start date.');
        }

        if ($this->weekdays === []) {
            throw new InvalidArgumentException('Recurring reservation must contain at least one weekday.');
        }
    }

    /**
     * @param list<string> $weekdays
     */
    public static function create(
        string $id,
        string $userId,
        string $deskId,
        DateTimeImmutable $startDate,
        DateTimeImmutable $endDate,
        array $weekdays,
    ): self {
        return new self(
            $id,
            $userId,
            $deskId,
            $startDate,
            $endDate,
            array_values($weekdays),
            true,
            new DateTimeImmutable('now'),
        );
    }

    public function id(): string
    {
        return $this->id;
    }

    public function userId(): string
    {
        return $this->userId;
    }

    public function deskId(): string
    {
        return $this->deskId;
    }

    public function startDate(): DateTimeImmutable
    {
        return $this->startDate;
    }

    public function endDate(): DateTimeImmutable
    {
        return $this->endDate;
    }

    /**
     * @return list<string>
     */
    public function weekdays(): array
    {
        return $this->weekdays;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
}
