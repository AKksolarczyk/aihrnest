<?php

declare(strict_types=1);

namespace App\Workspace\Application\Command\JoinDeskWaitlist;

use App\Workspace\Domain\Model\DeskWaitlistEntry;
use App\Workspace\Domain\Repository\DeskClaimRepositoryInterface;
use App\Workspace\Domain\Repository\DeskWaitlistRepositoryInterface;
use App\Workspace\Domain\Repository\OfficeLayoutRepositoryInterface;
use App\Workspace\Domain\Repository\UserRepositoryInterface;
use App\Workspace\Domain\Repository\VacationRepositoryInterface;
use App\Workspace\Domain\Repository\WorkspaceTransactionInterface;
use App\Workspace\Domain\Service\WorkspacePlanner;
use InvalidArgumentException;

final class JoinDeskWaitlistHandler
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly DeskWaitlistRepositoryInterface $deskWaitlistRepository,
        private readonly DeskClaimRepositoryInterface $deskClaimRepository,
        private readonly VacationRepositoryInterface $vacationRepository,
        private readonly OfficeLayoutRepositoryInterface $officeLayoutRepository,
        private readonly WorkspacePlanner $workspacePlanner,
        private readonly WorkspaceTransactionInterface $workspaceTransaction,
    ) {
    }

    public function handle(JoinDeskWaitlistCommand $command): void
    {
        $user = $this->userRepository->findById($command->userId);

        if ($user === null) {
            throw new InvalidArgumentException('Nie znaleziono wskazanego uzytkownika.');
        }

        $rooms = $this->officeLayoutRepository->findAllRooms();
        $deskMap = $this->workspacePlanner->buildDeskMap($rooms);

        if (!isset($deskMap[$command->deskId])) {
            throw new InvalidArgumentException('Wybrane biurko nie istnieje.');
        }

        if ($this->deskWaitlistRepository->findActiveEntry($command->userId, $command->deskId, $command->date) !== null) {
            throw new InvalidArgumentException('Uzytkownik jest juz na waitliscie dla tego biurka i dnia.');
        }

        $dailyPlan = $this->workspacePlanner->buildDailyPlan(
            $command->date,
            $this->userRepository->findAllOrderedByName(),
            $this->vacationRepository->findAll(),
            $this->deskClaimRepository->findAll(),
            $rooms,
        );

        if ($dailyPlan->deskIsAvailable($command->deskId)) {
            throw new InvalidArgumentException('Biurko jest wolne. Nie trzeba dodawac waitlisty.');
        }

        $this->deskWaitlistRepository->save(DeskWaitlistEntry::create(
            sprintf('wait-%s', bin2hex(random_bytes(4))),
            $command->userId,
            $command->deskId,
            $command->date,
        ));

        $this->workspaceTransaction->flush();
    }
}
