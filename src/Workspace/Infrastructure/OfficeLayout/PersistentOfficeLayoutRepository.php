<?php

declare(strict_types=1);

namespace App\Workspace\Infrastructure\OfficeLayout;

use App\Workspace\Domain\Model\Desk;
use App\Workspace\Domain\Model\Room;
use App\Workspace\Domain\Repository\DeskLabelRepositoryInterface;
use App\Workspace\Domain\Repository\OfficeLayoutRepositoryInterface;

final class PersistentOfficeLayoutRepository implements OfficeLayoutRepositoryInterface
{
    public function __construct(
        private readonly StaticOfficeLayoutRepository $staticOfficeLayoutRepository,
        private readonly DeskLabelRepositoryInterface $deskLabelRepository,
    ) {
    }

    public function findAllRooms(): array
    {
        $labels = $this->deskLabelRepository->findAllIndexedByDeskId();
        $rooms = [];

        foreach ($this->staticOfficeLayoutRepository->findAllRooms() as $room) {
            $desks = [];

            foreach ($room->desks() as $desk) {
                $customLabel = $labels[$desk->id()] ?? null;

                $desks[] = new Desk(
                    $desk->id(),
                    $customLabel?->label() ?? $desk->label(),
                );
            }

            $rooms[] = new Room($room->id(), $room->name(), $room->description(), $desks);
        }

        return $rooms;
    }
}
