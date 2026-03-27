<?php

declare(strict_types=1);

namespace App\Workspace\Application\Command\CloseIssueReport;

use App\Workspace\Domain\Repository\IssueReportRepositoryInterface;
use App\Workspace\Domain\Repository\WorkspaceTransactionInterface;
use InvalidArgumentException;

final class CloseIssueReportHandler
{
    public function __construct(
        private readonly IssueReportRepositoryInterface $issueReportRepository,
        private readonly WorkspaceTransactionInterface $workspaceTransaction,
    ) {
    }

    public function handle(CloseIssueReportCommand $command): void
    {
        $issueReport = $this->issueReportRepository->findById($command->issueReportId);

        if ($issueReport === null) {
            throw new InvalidArgumentException('Nie znaleziono wskazanego zgloszenia awarii.');
        }

        if (!$issueReport->isOpen()) {
            throw new InvalidArgumentException('To zgloszenie awarii jest juz zamkniete.');
        }

        $issueReport->close();
        $this->issueReportRepository->save($issueReport);
        $this->workspaceTransaction->flush();
    }
}
