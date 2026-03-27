<?php

declare(strict_types=1);

namespace App\Workspace\Domain\Model;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;

#[ORM\Entity]
#[ORM\Table(name: 'workspace_desk_claims')]
final class DeskClaim
{
    public function __construct(
        #[ORM\Id]
        #[ORM\Column(type: 'string', length: 32)]
        private string $id,
        #[ORM\Column(type: 'string', length: 32)]
        private string $userId,
        #[ORM\Column(type: 'string', length: 32)]
        private string $deskId,
        #[ORM\Column(type: 'datetime_immutable')]
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
