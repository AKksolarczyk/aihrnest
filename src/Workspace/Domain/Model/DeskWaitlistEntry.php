<?php

declare(strict_types=1);

namespace App\Workspace\Domain\Model;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;

#[ORM\Entity]
#[ORM\Table(name: 'workspace_desk_waitlist_entries')]
final class DeskWaitlistEntry
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
        #[ORM\Column(type: 'string', length: 16)]
        private string $status,
        #[ORM\Column(type: 'string', length: 64, nullable: true)]
        private ?string $claimToken,
        #[ORM\Column(type: 'datetime_immutable', nullable: true)]
        private ?DateTimeImmutable $offeredAt,
        #[ORM\Column(type: 'datetime_immutable')]
        private DateTimeImmutable $createdAt,
    ) {
        if ($this->id === '' || $this->userId === '' || $this->deskId === '') {
            throw new InvalidArgumentException('Desk waitlist identifiers cannot be empty.');
        }

        if (!in_array($this->status, ['waiting', 'offered', 'fulfilled', 'cancelled'], true)) {
            throw new InvalidArgumentException('Invalid waitlist status.');
        }
    }

    public static function create(
        string $id,
        string $userId,
        string $deskId,
        DateTimeImmutable $date,
    ): self {
        return new self($id, $userId, $deskId, $date, 'waiting', null, null, new DateTimeImmutable('now'));
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

    public function status(): string
    {
        return $this->status;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function claimToken(): ?string
    {
        return $this->claimToken;
    }

    public function offeredAt(): ?DateTimeImmutable
    {
        return $this->offeredAt;
    }

    public function isActive(): bool
    {
        return in_array($this->status, ['waiting', 'offered'], true);
    }

    public function matchesDate(DateTimeImmutable $date): bool
    {
        return $this->date->format('Y-m-d') === $date->format('Y-m-d');
    }

    public function offer(string $claimToken): void
    {
        if ($claimToken === '') {
            throw new InvalidArgumentException('Claim token cannot be empty.');
        }

        $this->status = 'offered';
        $this->claimToken = $claimToken;
        $this->offeredAt = new DateTimeImmutable('now');
    }

    public function fulfill(): void
    {
        $this->status = 'fulfilled';
        $this->claimToken = null;
        $this->offeredAt = null;
    }

    public function cancel(): void
    {
        $this->status = 'cancelled';
        $this->claimToken = null;
        $this->offeredAt = null;
    }
}
