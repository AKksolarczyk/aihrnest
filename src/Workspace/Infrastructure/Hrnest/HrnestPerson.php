<?php

declare(strict_types=1);

namespace App\Workspace\Infrastructure\Hrnest;

final readonly class HrnestPerson
{
    public function __construct(
        public string $externalId,
        public string $name,
        public string $email,
        public string $team,
        public ?string $deskId,
    ) {
    }
}
