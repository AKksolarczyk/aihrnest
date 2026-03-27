<?php

declare(strict_types=1);

namespace App\Workspace\Application\Command\ReleaseDeskClaim;

use App\Workspace\Application\Command\DeskWaitlistOffer\NotifyDeskWaitlistAvailabilityHandler;
use App\Workspace\Domain\Repository\DeskClaimRepositoryInterface;
use App\Workspace\Domain\Repository\WorkspaceTransactionInterface;
use InvalidArgumentException;

final class ReleaseDeskClaimHandler
{
    public function __construct(
        private readonly DeskClaimRepositoryInterface $deskClaimRepository,
        private readonly WorkspaceTransactionInterface $workspaceTransaction,
        private readonly NotifyDeskWaitlistAvailabilityHandler $notifyDeskWaitlistAvailabilityHandler,
    ) {
    }

    public function handle(ReleaseDeskClaimCommand $command): void
    {
        $deskClaim = $this->deskClaimRepository->findOneForUserAndDate($command->userId, $command->date);

        if ($deskClaim === null) {
            throw new InvalidArgumentException('Brak zajecia biurka do zwolnienia w wybranym dniu.');
        }

        $deskId = $deskClaim->deskId();
        $date = $deskClaim->date();

        $this->deskClaimRepository->remove($deskClaim);
        $this->workspaceTransaction->flush();

        $this->notifyDeskWaitlistAvailabilityHandler->handle($deskId, $date);
    }
}
