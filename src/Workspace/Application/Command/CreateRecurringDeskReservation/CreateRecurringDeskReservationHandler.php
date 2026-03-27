<?php

declare(strict_types=1);

namespace App\Workspace\Application\Command\CreateRecurringDeskReservation;

use App\Workspace\Domain\Model\DeskClaim;
use App\Workspace\Domain\Model\RecurringDeskReservation;
use App\Workspace\Domain\Repository\DeskClaimRepositoryInterface;
use App\Workspace\Domain\Repository\IssueReportRepositoryInterface;
use App\Workspace\Domain\Repository\OfficeLayoutRepositoryInterface;
use App\Workspace\Domain\Repository\RecurringDeskReservationRepositoryInterface;
use App\Workspace\Domain\Repository\UserRepositoryInterface;
use App\Workspace\Domain\Repository\VacationRepositoryInterface;
use App\Workspace\Domain\Repository\WorkspaceTransactionInterface;
use App\Workspace\Domain\Service\WorkspacePlanner;
use DateInterval;
use InvalidArgumentException;

final class CreateRecurringDeskReservationHandler
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly DeskClaimRepositoryInterface $deskClaimRepository,
        private readonly VacationRepositoryInterface $vacationRepository,
        private readonly RecurringDeskReservationRepositoryInterface $recurringDeskReservationRepository,
        private readonly IssueReportRepositoryInterface $issueReportRepository,
        private readonly OfficeLayoutRepositoryInterface $officeLayoutRepository,
        private readonly WorkspacePlanner $workspacePlanner,
        private readonly WorkspaceTransactionInterface $workspaceTransaction,
    ) {
    }

    public function handle(CreateRecurringDeskReservationCommand $command): CreateRecurringDeskReservationResult
    {
        if ($command->endDate < $command->startDate) {
            throw new InvalidArgumentException('Data koncowa nie moze byc wczesniejsza niz poczatkowa.');
        }

        if ($command->weekdays === []) {
            throw new InvalidArgumentException('Wybierz przynajmniej jeden dzien tygodnia.');
        }

        $user = $this->userRepository->findById($command->userId);

        if ($user === null) {
            throw new InvalidArgumentException('Nie znaleziono wskazanego uzytkownika.');
        }

        $rooms = $this->officeLayoutRepository->findAllRooms();
        $deskMap = $this->workspacePlanner->buildDeskMap($rooms);

        if (!isset($deskMap[$command->deskId])) {
            throw new InvalidArgumentException('Wybrane biurko nie istnieje.');
        }

        $normalizedWeekdays = array_values(array_unique(array_map('strtolower', $command->weekdays)));
        $users = $this->userRepository->findAllOrderedByName();
        $vacations = $this->vacationRepository->findAll();
        $deskClaims = $this->deskClaimRepository->findAll();
        $issueReports = $this->issueReportRepository->findOpen();
        $createdClaims = 0;
        $skippedClaims = 0;

        if (isset($this->workspacePlanner->indexUnavailableDeskIds($issueReports)[$command->deskId])) {
            throw new InvalidArgumentException('Wybrane biurko jest tymczasowo niedostepne z powodu otwartego zgloszenia awarii.');
        }

        for ($date = $command->startDate; $date <= $command->endDate; $date = $date->add(new DateInterval('P1D'))) {
            if (!in_array(strtolower($date->format('l')), $normalizedWeekdays, true)) {
                continue;
            }

            $dailyPlan = $this->workspacePlanner->buildDailyPlan($date, $users, $vacations, $deskClaims, $rooms, $issueReports);

            if ($dailyPlan->userHasDesk($command->userId) || !$dailyPlan->deskIsAvailable($command->deskId)) {
                ++$skippedClaims;
                continue;
            }

            $claim = new DeskClaim(
                sprintf('claim-%s', bin2hex(random_bytes(4))),
                $command->userId,
                $command->deskId,
                $date,
            );

            $this->deskClaimRepository->add($claim);
            $deskClaims[] = $claim;
            ++$createdClaims;
        }

        $this->recurringDeskReservationRepository->add(RecurringDeskReservation::create(
            sprintf('rr-%s', bin2hex(random_bytes(4))),
            $command->userId,
            $command->deskId,
            $command->startDate,
            $command->endDate,
            $normalizedWeekdays,
        ));

        $this->workspaceTransaction->flush();

        return new CreateRecurringDeskReservationResult($createdClaims, $skippedClaims);
    }
}
