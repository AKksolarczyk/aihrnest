<?php

declare(strict_types=1);

namespace App\Workspace\Application\Command\ReportIssue;

final readonly class ReportIssueResult
{
    public function __construct(
        public int $notifiedAdminsCount,
    ) {
    }
}
