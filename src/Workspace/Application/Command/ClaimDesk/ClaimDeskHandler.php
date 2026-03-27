<?php

declare(strict_types=1);

namespace App\Workspace\Application\Command\ClaimDesk;

use App\Workspace\Domain\Model\DeskClaim;
use App\Workspace\Domain\Repository\OfficeLayoutRepositoryInterface;
use App\Workspace\Domain\Repository\WorkspaceStateRepositoryInterface;
use App\Workspace\Domain\Service\WorkspacePlanner;
use InvalidArgumentException;

final class ClaimDeskHandler
{
    public function __construct(
        private readonly WorkspaceStateRepositoryInterface $workspaceStateRepository,
        private readonly OfficeLayoutRepositoryInterface $officeLayoutRepository,
        private readonly WorkspacePlanner $workspacePlanner,
    ) {
    }

    public function handle(ClaimDeskCommand $command): void
    {
        $workspaceState = $this->workspaceStateRepository->load();
        $user = $workspaceState->findUser($command->userId);

        if ($user === null) {
            throw new InvalidArgumentException('Nie znaleziono wskazanego uzytkownika.');
        }

        $rooms = $this->officeLayoutRepository->findAllRooms();
        $deskMap = $this->workspacePlanner->buildDeskMap($rooms);

        if (!isset($deskMap[$command->deskId])) {
            throw new InvalidArgumentException('Wybrane biurko nie istnieje.');
        }

        $dailyPlan = $this->workspacePlanner->buildDailyPlan($command->date, $workspaceState, $rooms);

        if ($dailyPlan->userHasDesk($command->userId)) {
            throw new InvalidArgumentException('Ten uzytkownik ma juz przydzielone biurko w wybranym dniu.');
        }

        if (!$dailyPlan->deskIsAvailable($command->deskId)) {
            throw new InvalidArgumentException('Wybrane biurko nie jest wolne.');
        }

        $workspaceState->addDeskClaim(new DeskClaim(
            sprintf('claim-%s', bin2hex(random_bytes(4))),
            $command->userId,
            $command->deskId,
            $command->date,
        ));

        $this->workspaceStateRepository->save($workspaceState);
    }
}
