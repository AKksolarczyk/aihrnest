<?php

declare(strict_types=1);

namespace App\Workspace\Infrastructure\Persistence;

use App\Workspace\Domain\Model\IssueReport;
use App\Workspace\Domain\Repository\IssueReportRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineIssueReportRepository implements IssueReportRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function findAll(): array
    {
        /** @var list<IssueReport> $issues */
        $issues = $this->entityManager->getRepository(IssueReport::class)->findBy([], ['reportedAt' => 'DESC']);

        return $issues;
    }

    public function findAllForUser(string $userId): array
    {
        /** @var list<IssueReport> $issues */
        $issues = $this->entityManager->getRepository(IssueReport::class)->findBy(['userId' => $userId], ['reportedAt' => 'DESC']);

        return $issues;
    }

    public function findOpen(): array
    {
        /** @var list<IssueReport> $issues */
        $issues = $this->entityManager->getRepository(IssueReport::class)->findBy(['status' => 'open'], ['reportedAt' => 'DESC']);

        return $issues;
    }

    public function findById(string $issueReportId): ?IssueReport
    {
        return $this->entityManager->find(IssueReport::class, $issueReportId);
    }

    public function add(IssueReport $issueReport): void
    {
        $this->entityManager->persist($issueReport);
    }

    public function save(IssueReport $issueReport): void
    {
        $this->entityManager->persist($issueReport);
    }
}
