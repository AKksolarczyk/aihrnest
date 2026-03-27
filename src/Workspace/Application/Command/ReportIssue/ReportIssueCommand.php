<?php

declare(strict_types=1);

namespace App\Workspace\Application\Command\ReportIssue;

final readonly class ReportIssueCommand
{
    public function __construct(
        public string $userId,
        public ?string $deskId,
        public ?string $roomId,
        public string $category,
        public string $description,
    ) {
    }
}
