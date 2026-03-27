<?php

declare(strict_types=1);

namespace App\Workspace\Application\Command\RegisterUser;

final readonly class RegisterUserCommand
{
    /**
     * @param list<string> $schedule
     */
    public function __construct(
        public string $name,
        public string $email,
        public string $team,
        public string $locale,
        public string $plainPassword,
        public ?string $assignedDeskId,
        public array $schedule,
    ) {
    }
}
