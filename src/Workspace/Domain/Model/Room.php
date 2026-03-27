<?php

declare(strict_types=1);

namespace App\Workspace\Domain\Model;

final class Room
{
    /**
     * @param list<Desk> $desks
     */
    public function __construct(
        private string $id,
        private string $name,
        private string $description,
        private array $desks,
    ) {
    }

    public function id(): string
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function description(): string
    {
        return $this->description;
    }

    /**
     * @return list<Desk>
     */
    public function desks(): array
    {
        return $this->desks;
    }
}
