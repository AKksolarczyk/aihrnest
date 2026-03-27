<?php

declare(strict_types=1);

namespace App\Workspace\Application\Command\ReportIssue;

use App\Workspace\Domain\Model\IssueReport;
use App\Workspace\Domain\Repository\IssueReportRepositoryInterface;
use App\Workspace\Domain\Repository\OfficeLayoutRepositoryInterface;
use App\Workspace\Domain\Repository\UserRepositoryInterface;
use App\Workspace\Domain\Repository\WorkspaceTransactionInterface;
use App\Workspace\Domain\Service\WorkspacePlanner;
use InvalidArgumentException;

final class ReportIssueHandler
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly IssueReportRepositoryInterface $issueReportRepository,
        private readonly OfficeLayoutRepositoryInterface $officeLayoutRepository,
        private readonly WorkspacePlanner $workspacePlanner,
        private readonly WorkspaceTransactionInterface $workspaceTransaction,
    ) {
    }

    public function handle(ReportIssueCommand $command): void
    {
        if ($this->userRepository->findById($command->userId) === null) {
            throw new InvalidArgumentException('Nie znaleziono wskazanego uzytkownika.');
        }

        $rooms = $this->officeLayoutRepository->findAllRooms();
        $roomIds = array_map(static fn ($room): string => $room->id(), $rooms);
        $deskMap = $this->workspacePlanner->buildDeskMap($rooms);
        $deskId = $command->deskId !== '' ? $command->deskId : null;
        $roomId = $command->roomId !== '' ? $command->roomId : null;

        if ($deskId !== null && !isset($deskMap[$deskId])) {
            throw new InvalidArgumentException('Wybrane biurko nie istnieje.');
        }

        if ($roomId !== null && !in_array($roomId, $roomIds, true)) {
            throw new InvalidArgumentException('Wybrane pomieszczenie nie istnieje.');
        }

        $this->issueReportRepository->add(IssueReport::report(
            sprintf('issue-%s', bin2hex(random_bytes(4))),
            $command->userId,
            $deskId,
            $roomId,
            $command->category,
            $command->description,
        ));

        $this->workspaceTransaction->flush();
    }
}
