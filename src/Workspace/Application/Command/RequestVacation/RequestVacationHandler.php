<?php

declare(strict_types=1);

namespace App\Workspace\Application\Command\RequestVacation;

use App\Workspace\Domain\Model\Vacation;
use App\Workspace\Domain\Repository\WorkspaceStateRepositoryInterface;
use App\Workspace\Domain\Service\BusinessDayCounter;
use InvalidArgumentException;

final class RequestVacationHandler
{
    public function __construct(
        private readonly WorkspaceStateRepositoryInterface $workspaceStateRepository,
        private readonly BusinessDayCounter $businessDayCounter,
    ) {
    }

    public function handle(RequestVacationCommand $command): void
    {
        if ($command->endDate < $command->startDate) {
            throw new InvalidArgumentException('Data koncowa urlopu nie moze byc wczesniejsza niz poczatkowa.');
        }

        $workspaceState = $this->workspaceStateRepository->load();
        $user = $workspaceState->findUser($command->userId);

        if ($user === null) {
            throw new InvalidArgumentException('Nie znaleziono wskazanego uzytkownika.');
        }

        $requestedDays = $this->businessDayCounter->countBetween($command->startDate, $command->endDate);

        if ($requestedDays < 1) {
            throw new InvalidArgumentException('W wybranym zakresie nie ma zadnych dni roboczych do rozliczenia.');
        }

        $user->consumeVacationDays($requestedDays);
        $workspaceState->addVacation(new Vacation(
            sprintf('vac-%s', bin2hex(random_bytes(4))),
            $command->userId,
            $command->startDate,
            $command->endDate,
        ));

        $this->workspaceStateRepository->save($workspaceState);
    }
}
