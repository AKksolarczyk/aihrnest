<?php

declare(strict_types=1);

namespace App\Workspace\Domain\Model;

use DateTimeImmutable;
use InvalidArgumentException;

final class DeskClaim
{
    public function __construct(
        private string $id,
        private string $userId,
        private string $deskId,
        private DateTimeImmutable $date,
    ) {
        if ($this->id === '' || $this->userId === '' || $this->deskId === '') {
            throw new InvalidArgumentException('Desk claim identifiers cannot be empty.');
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

    public function deskId(): string
    {
        return $this->deskId;
    }

    public function date(): DateTimeImmutable
    {
        return $this->date;
    }

    public function matchesDate(DateTimeImmutable $date): bool
    {
        return $this->date->format('Y-m-d') === $date->format('Y-m-d');
    }
}
