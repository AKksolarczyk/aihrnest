<?php

declare(strict_types=1);

namespace App\Workspace\Infrastructure\OfficeLayout;

use App\Workspace\Domain\Model\Desk;
use App\Workspace\Domain\Model\Room;
use App\Workspace\Domain\Repository\OfficeLayoutRepositoryInterface;

final class StaticOfficeLayoutRepository implements OfficeLayoutRepositoryInterface
{
    /**
     * @return list<Room>
     */
    public function findAllRooms(): array
    {
        return [
            new Room('focus-room', 'Focus Room', 'Ciche stanowiska dla pracy wymagajacej skupienia.', [
                new Desk('A-01', 'A-01'),
                new Desk('A-02', 'A-02'),
                new Desk('A-03', 'A-03'),
                new Desk('A-04', 'A-04'),
            ]),
            new Room('client-room', 'Client Room', 'Pomieszczenie dla zespolow pracujacych blisko operacji i spotkan.', [
                new Desk('B-01', 'B-01'),
                new Desk('B-02', 'B-02'),
                new Desk('B-03', 'B-03'),
                new Desk('B-04', 'B-04'),
            ]),
            new Room('makers-room', 'Makers Room', 'Przestrzen dla testow, prototypow i pracy mieszanej.', [
                new Desk('C-01', 'C-01'),
                new Desk('C-02', 'C-02'),
                new Desk('C-03', 'C-03'),
                new Desk('C-04', 'C-04'),
            ]),
        ];
    }
}
