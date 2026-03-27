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
        private readonly IssueReportNotificationMailer $issueReportNotificationMailer,
    ) {
    }

    public function handle(ReportIssueCommand $command): ReportIssueResult
    {
        $reporter = $this->userRepository->findById($command->userId);

        if ($reporter === null) {
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

        $issueReport = IssueReport::report(
            sprintf('issue-%s', bin2hex(random_bytes(4))),
            $command->userId,
            $deskId,
            $roomId,
            $command->category,
            $command->description,
        );
        $this->issueReportRepository->add($issueReport);

        $this->workspaceTransaction->flush();

        $resourceLabel = $deskId !== null
            ? sprintf('%s / %s', $deskMap[$deskId]['label'] ?? $deskId, $deskMap[$deskId]['roomName'] ?? '')
            : ($roomId !== null ? $this->resolveRoomName($rooms, $roomId) : '');

        $notifiedAdminsCount = 0;

        foreach ($this->userRepository->findAllOrderedByName() as $recipient) {
            if (!$recipient->isActive() || !in_array('ROLE_ADMIN', $recipient->getRoles(), true)) {
                continue;
            }

            $this->issueReportNotificationMailer->send($recipient, $reporter, $issueReport, trim($resourceLabel));
            ++$notifiedAdminsCount;
        }

        return new ReportIssueResult($notifiedAdminsCount);
    }

    /**
     * @param list<object> $rooms
     */
    private function resolveRoomName(array $rooms, string $roomId): string
    {
        foreach ($rooms as $room) {
            if (method_exists($room, 'id') && method_exists($room, 'name') && $room->id() === $roomId) {
                return $room->name();
            }
        }

        return $roomId;
    }
}
