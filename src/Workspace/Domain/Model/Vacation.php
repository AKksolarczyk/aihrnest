<?php

declare(strict_types=1);

namespace App\Workspace\Domain\Model;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;

#[ORM\Entity]
#[ORM\Table(name: 'workspace_vacations')]
final class Vacation
{
    public function __construct(
        #[ORM\Id]
        #[ORM\Column(type: 'string', length: 32)]
        private string $id,
        #[ORM\Column(type: 'string', length: 32)]
        private string $userId,
        #[ORM\Column(type: 'datetime_immutable')]
        private DateTimeImmutable $startDate,
        #[ORM\Column(type: 'datetime_immutable')]
        private DateTimeImmutable $endDate,
    ) {
        if ($this->id === '' || $this->userId === '') {
            throw new InvalidArgumentException('Vacation identifiers cannot be empty.');
        }

        if ($this->endDate < $this->startDate) {
            throw new InvalidArgumentException('Data koncowa urlopu nie moze byc wczesniejsza niz poczatkowa.');
        }
    }

    public function id(): string
    {
        return $this->id;
    }

    public function userId(): string
    {
        return $this->userId;
    }

    public function startDate(): DateTimeImmutable
    {
        return $this->startDate;
    }

    public function endDate(): DateTimeImmutable
    {
        return $this->endDate;
    }

    public function includes(DateTimeImmutable $date): bool
    {
        return $date >= $this->startDate && $date <= $this->endDate;
    }

    public function overlapsWith(DateTimeImmutable $startDate, DateTimeImmutable $endDate): bool
    {
        return $this->startDate <= $endDate && $startDate <= $this->endDate;
    }
}
