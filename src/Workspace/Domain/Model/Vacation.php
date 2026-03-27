<?php

declare(strict_types=1);

namespace App\Workspace\Domain\Model;

use DateTimeImmutable;
use InvalidArgumentException;

final class Vacation
{
    public function __construct(
        private string $id,
        private string $userId,
        private DateTimeImmutable $startDate,
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
}
