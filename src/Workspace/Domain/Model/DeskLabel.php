<?php

declare(strict_types=1);

namespace App\Workspace\Domain\Model;

use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;

#[ORM\Entity]
#[ORM\Table(name: 'workspace_desk_labels')]
final class DeskLabel
{
    public function __construct(
        #[ORM\Id]
        #[ORM\Column(type: 'string', length: 32)]
        private string $deskId,
        #[ORM\Column(type: 'string', length: 255)]
        private string $label,
    ) {
        $this->deskId = trim($this->deskId);
        $this->rename($this->label);

        if ($this->deskId === '') {
            throw new InvalidArgumentException('Desk id cannot be empty.');
        }
    }

    public function deskId(): string
    {
        return $this->deskId;
    }

    public function label(): string
    {
        return $this->label;
    }

    public function rename(string $label): void
    {
        $label = trim($label);

        if ($label === '') {
            throw new InvalidArgumentException('Desk label cannot be empty.');
        }

        $this->label = $label;
    }
}
