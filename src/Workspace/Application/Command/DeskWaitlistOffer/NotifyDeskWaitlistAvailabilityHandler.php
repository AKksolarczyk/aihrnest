<?php

declare(strict_types=1);

namespace App\Workspace\Application\Command\DeskWaitlistOffer;

use App\Workspace\Domain\Repository\DeskWaitlistRepositoryInterface;
use App\Workspace\Domain\Repository\IssueReportRepositoryInterface;
use App\Workspace\Domain\Repository\OfficeLayoutRepositoryInterface;
use App\Workspace\Domain\Repository\UserRepositoryInterface;
use App\Workspace\Domain\Repository\WorkspaceTransactionInterface;
use App\Workspace\Domain\Service\WorkspacePlanner;
use DateTimeImmutable;

final class NotifyDeskWaitlistAvailabilityHandler
{
    public function __construct(
        private readonly DeskWaitlistRepositoryInterface $deskWaitlistRepository,
        private readonly UserRepositoryInterface $userRepository,
        private readonly IssueReportRepositoryInterface $issueReportRepository,
        private readonly OfficeLayoutRepositoryInterface $officeLayoutRepository,
        private readonly WorkspacePlanner $workspacePlanner,
        private readonly DeskWaitlistOfferMailer $mailer,
        private readonly WorkspaceTransactionInterface $workspaceTransaction,
    ) {
    }

    public function handle(string $deskId, DateTimeImmutable $date): void
    {
        if (isset($this->workspacePlanner->indexUnavailableDeskIds($this->issueReportRepository->findOpen())[$deskId])) {
            return;
        }

        $entries = $this->deskWaitlistRepository->findWaitingForDeskAndDate($deskId, $date);
        $nextEntry = $entries[0] ?? null;

        if ($nextEntry === null) {
            return;
        }

        $user = $this->userRepository->findById($nextEntry->userId());

        if ($user === null || !$user->isActive()) {
            return;
        }

        $deskMap = $this->workspacePlanner->buildDeskMap($this->officeLayoutRepository->findAllRooms());
        $nextEntry->offer(bin2hex(random_bytes(32)));
        $this->deskWaitlistRepository->save($nextEntry);
        $this->workspaceTransaction->flush();

        $this->mailer->send(
            $user,
            $nextEntry,
            $deskMap[$deskId]['label'] ?? $deskId,
            $deskMap[$deskId]['roomName'] ?? '',
        );
    }
}
