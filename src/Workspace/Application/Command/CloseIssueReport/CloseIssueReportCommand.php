<?php

declare(strict_types=1);

namespace App\Workspace\Application\Command\CloseIssueReport;

final readonly class CloseIssueReportCommand
{
    public function __construct(
        public string $issueReportId,
    ) {
    }
}
