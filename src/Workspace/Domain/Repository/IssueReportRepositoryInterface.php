<?php

declare(strict_types=1);

namespace App\Workspace\Domain\Repository;

use App\Workspace\Domain\Model\IssueReport;

interface IssueReportRepositoryInterface
{
    /**
     * @return list<IssueReport>
     */
    public function findAll(): array;

    /**
     * @return list<IssueReport>
     */
    public function findAllForUser(string $userId): array;

    /**
     * @return list<IssueReport>
     */
    public function findOpen(): array;

    public function findById(string $issueReportId): ?IssueReport;

    public function add(IssueReport $issueReport): void;

    public function save(IssueReport $issueReport): void;
}
