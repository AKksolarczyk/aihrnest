<?php

declare(strict_types=1);

namespace App\Workspace\Application\Command\ClaimDesk;

use App\Workspace\Domain\Model\DeskClaim;
use App\Workspace\Domain\Repository\DeskClaimRepositoryInterface;
use App\Workspace\Domain\Repository\OfficeLayoutRepositoryInterface;
use App\Workspace\Domain\Repository\UserRepositoryInterface;
use App\Workspace\Domain\Repository\VacationRepositoryInterface;
use App\Workspace\Domain\Repository\WorkspaceTransactionInterface;
use App\Workspace\Domain\Service\WorkspacePlanner;
use InvalidArgumentException;

final class ClaimDeskHandler
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly VacationRepositoryInterface $vacationRepository,
        private readonly DeskClaimRepositoryInterface $deskClaimRepository,
        private readonly WorkspaceTransactionInterface $workspaceTransaction,
        private readonly OfficeLayoutRepositoryInterface $officeLayoutRepository,
        private readonly WorkspacePlanner $workspacePlanner,
    ) {
    }

    public function handle(ClaimDeskCommand $command): void
    {
        $user = $this->userRepository->findById($command->userId);

        if ($user === null) {
            throw new InvalidArgumentException('Nie znaleziono wskazanego uzytkownika.');
        }

        $rooms = $this->officeLayoutRepository->findAllRooms();
        $deskMap = $this->workspacePlanner->buildDeskMap($rooms);
        $users = $this->userRepository->findAllOrderedByName();
        $vacations = $this->vacationRepository->findAll();
        $deskClaims = $this->deskClaimRepository->findAll();

        if (!isset($deskMap[$command->deskId])) {
            throw new InvalidArgumentException('Wybrane biurko nie istnieje.');
        }

        $dailyPlan = $this->workspacePlanner->buildDailyPlan($command->date, $users, $vacations, $deskClaims, $rooms);

        if ($dailyPlan->userHasDesk($command->userId)) {
            throw new InvalidArgumentException('Ten uzytkownik ma juz przydzielone biurko w wybranym dniu.');
        }

        if (!$dailyPlan->deskIsAvailable($command->deskId)) {
            throw new InvalidArgumentException('Wybrane biurko nie jest wolne.');
        }

        $this->deskClaimRepository->add(new DeskClaim(
            sprintf('claim-%s', bin2hex(random_bytes(4))),
            $command->userId,
            $command->deskId,
            $command->date,
        ));
        $this->workspaceTransaction->flush();
    }
}
