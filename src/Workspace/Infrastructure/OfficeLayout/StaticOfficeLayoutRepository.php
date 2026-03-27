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
            new Room('focus-room', 'Pomieszczenie 1', 'Uklad zgodny ze specyfikacja: 11 biurek z bocznym przejsciem i pojedynczym stanowiskiem w dolnym rogu.', [
                new Desk('A-01', 'A-01'),
                new Desk('A-02', 'A-02'),
                new Desk('A-03', 'A-03'),
                new Desk('A-04', 'A-04'),
                new Desk('A-05', 'A-05'),
                new Desk('A-06', 'A-06'),
                new Desk('A-07', 'A-07'),
                new Desk('A-08', 'A-08'),
                new Desk('A-09', 'A-09'),
                new Desk('A-10', 'A-10'),
                new Desk('A-11', 'A-11'),
            ]),
            new Room('client-room', 'Pomieszczenie 2', 'Uklad zgodny ze specyfikacja: dwa rzedu po 5 biurek z przejsciem posrodku.', [
                new Desk('B-01', 'B-01'),
                new Desk('B-02', 'B-02'),
                new Desk('B-03', 'B-03'),
                new Desk('B-04', 'B-04'),
                new Desk('B-05', 'B-05'),
                new Desk('B-06', 'B-06'),
                new Desk('B-07', 'B-07'),
                new Desk('B-08', 'B-08'),
                new Desk('B-09', 'B-09'),
                new Desk('B-10', 'B-10'),
            ]),
            new Room('makers-room', 'Pomieszczenie 3', 'Uklad zgodny ze specyfikacja: dwa rzedu po 5 biurek z przejsciem posrodku.', [
                new Desk('C-01', 'C-01'),
                new Desk('C-02', 'C-02'),
                new Desk('C-03', 'C-03'),
                new Desk('C-04', 'C-04'),
                new Desk('C-05', 'C-05'),
                new Desk('C-06', 'C-06'),
                new Desk('C-07', 'C-07'),
                new Desk('C-08', 'C-08'),
                new Desk('C-09', 'C-09'),
                new Desk('C-10', 'C-10'),
            ]),
        ];
    }
}
