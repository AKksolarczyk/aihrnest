<?php

declare(strict_types=1);

namespace App\Workspace\Domain\Model;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;

#[ORM\Entity]
#[ORM\Table(name: 'workspace_issue_reports')]
final class IssueReport
{
    public function __construct(
        #[ORM\Id]
        #[ORM\Column(type: 'string', length: 32)]
        private string $id,
        #[ORM\Column(type: 'string', length: 32)]
        private string $userId,
        #[ORM\Column(type: 'string', length: 32, nullable: true)]
        private ?string $deskId,
        #[ORM\Column(type: 'string', length: 32, nullable: true)]
        private ?string $roomId,
        #[ORM\Column(type: 'string', length: 32)]
        private string $category,
        #[ORM\Column(type: 'text')]
        private string $description,
        #[ORM\Column(type: 'string', length: 16)]
        private string $status,
        #[ORM\Column(type: 'datetime_immutable')]
        private DateTimeImmutable $reportedAt,
    ) {
        if ($this->id === '' || $this->userId === '' || $this->category === '') {
            throw new InvalidArgumentException('Issue report identifiers cannot be empty.');
        }

        if ($this->deskId === null && $this->roomId === null) {
            throw new InvalidArgumentException('Issue report must point to desk or room.');
        }

        if (trim($this->description) === '') {
            throw new InvalidArgumentException('Issue report description cannot be empty.');
        }
    }

    public static function report(
        string $id,
        string $userId,
        ?string $deskId,
        ?string $roomId,
        string $category,
        string $description,
    ): self {
        return new self(
            $id,
            $userId,
            $deskId,
            $roomId,
            trim($category),
            trim($description),
            'open',
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

    public function deskId(): ?string
    {
        return $this->deskId;
    }

    public function roomId(): ?string
    {
        return $this->roomId;
    }

    public function category(): string
    {
        return $this->category;
    }

    public function description(): string
    {
        return $this->description;
    }

    public function status(): string
    {
        return $this->status;
    }

    public function reportedAt(): DateTimeImmutable
    {
        return $this->reportedAt;
    }
}
