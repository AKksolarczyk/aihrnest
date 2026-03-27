<?php

declare(strict_types=1);

namespace App\Workspace\Domain\Model;

final class Desk
{
    public function __construct(
        private string $id,
        private string $label,
    ) {
    }

    public function id(): string
    {
        return $this->id;
    }

    public function label(): string
    {
        return $this->label;
    }
}
