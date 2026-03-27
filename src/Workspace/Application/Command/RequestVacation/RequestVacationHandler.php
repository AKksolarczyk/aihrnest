<?php

declare(strict_types=1);

namespace App\Workspace\Application\Command\RequestVacation;

use App\Workspace\Application\Command\DeskWaitlistOffer\NotifyDeskWaitlistAvailabilityHandler;
use App\Workspace\Domain\Model\Vacation;
use App\Workspace\Domain\Repository\DeskClaimRepositoryInterface;
use App\Workspace\Domain\Repository\UserRepositoryInterface;
use App\Workspace\Domain\Repository\VacationRepositoryInterface;
use App\Workspace\Domain\Repository\WorkspaceTransactionInterface;
use App\Workspace\Domain\Service\BusinessDayCounter;
use DateInterval;
use InvalidArgumentException;

final class RequestVacationHandler
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly VacationRepositoryInterface $vacationRepository,
        private readonly DeskClaimRepositoryInterface $deskClaimRepository,
        private readonly WorkspaceTransactionInterface $workspaceTransaction,
        private readonly BusinessDayCounter $businessDayCounter,
        private readonly NotifyDeskWaitlistAvailabilityHandler $notifyDeskWaitlistAvailabilityHandler,
    ) {
    }

    public function handle(RequestVacationCommand $command): void
    {
        if ($command->endDate < $command->startDate) {
            throw new InvalidArgumentException('Data koncowa urlopu nie moze byc wczesniejsza niz poczatkowa.');
        }

        $user = $this->userRepository->findById($command->userId);

        if ($user === null) {
            throw new InvalidArgumentException('Nie znaleziono wskazanego uzytkownika.');
        }

        $requestedDays = $this->businessDayCounter->countBetween($command->startDate, $command->endDate);

        if ($requestedDays < 1) {
            throw new InvalidArgumentException('W wybranym zakresie nie ma zadnych dni roboczych do rozliczenia.');
        }

        foreach ($this->vacationRepository->findAllForUser($command->userId) as $existingVacation) {
            if ($existingVacation->overlapsWith($command->startDate, $command->endDate)) {
                throw new InvalidArgumentException('Uzytkownik ma juz urlop w podanym zakresie.');
            }
        }

        $releasedDesks = [];
        $deskClaimsToRemove = $this->deskClaimRepository->findAllForUserInRange($command->userId, $command->startDate, $command->endDate);

        foreach ($deskClaimsToRemove as $deskClaim) {
            $releasedDesks[$deskClaim->deskId().'#'.$deskClaim->date()->format('Y-m-d')] = [
                'deskId' => $deskClaim->deskId(),
                'date' => $deskClaim->date(),
            ];
            $this->deskClaimRepository->remove($deskClaim);
        }

        if ($user->hasAssignedDesk()) {
            for ($date = $command->startDate; $date <= $command->endDate; $date = $date->add(new DateInterval('P1D'))) {
                if (!$user->isScheduledOn($date)) {
                    continue;
                }

                $releasedDesks[$user->assignedDeskId().'#'.$date->format('Y-m-d')] = [
                    'deskId' => $user->assignedDeskId(),
                    'date' => $date,
                ];
            }
        }

        $user->consumeVacationDays($requestedDays);
        $this->userRepository->save($user);
        $this->vacationRepository->add(new Vacation(
            sprintf('vac-%s', bin2hex(random_bytes(4))),
            $command->userId,
            $command->startDate,
            $command->endDate,
        ));
        $this->workspaceTransaction->flush();

        foreach ($releasedDesks as $releasedDesk) {
            $this->notifyDeskWaitlistAvailabilityHandler->handle($releasedDesk['deskId'], $releasedDesk['date']);
        }
    }
}
