<?php

namespace App\Service;

final class OfficeLayoutProvider
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function getRooms(): array
    {
        return [
            [
                'id' => 'focus-room',
                'name' => 'Focus Room',
                'description' => 'Ciche stanowiska dla pracy wymagajacej skupienia.',
                'desks' => [
                    ['id' => 'A-01', 'label' => 'A-01', 'x' => '1 / 2'],
                    ['id' => 'A-02', 'label' => 'A-02', 'x' => '2 / 3'],
                    ['id' => 'A-03', 'label' => 'A-03', 'x' => '1 / 2'],
                    ['id' => 'A-04', 'label' => 'A-04', 'x' => '2 / 3'],
                ],
            ],
            [
                'id' => 'client-room',
                'name' => 'Client Room',
                'description' => 'Pomieszczenie dla zespolow pracujacych blisko operacji i spotkan.',
                'desks' => [
                    ['id' => 'B-01', 'label' => 'B-01', 'x' => '1 / 2'],
                    ['id' => 'B-02', 'label' => 'B-02', 'x' => '2 / 3'],
                    ['id' => 'B-03', 'label' => 'B-03', 'x' => '1 / 2'],
                    ['id' => 'B-04', 'label' => 'B-04', 'x' => '2 / 3'],
                ],
            ],
            [
                'id' => 'makers-room',
                'name' => 'Makers Room',
                'description' => 'Przestrzen dla testow, prototypow i pracy mieszanej.',
                'desks' => [
                    ['id' => 'C-01', 'label' => 'C-01', 'x' => '1 / 2'],
                    ['id' => 'C-02', 'label' => 'C-02', 'x' => '2 / 3'],
                    ['id' => 'C-03', 'label' => 'C-03', 'x' => '1 / 2'],
                    ['id' => 'C-04', 'label' => 'C-04', 'x' => '2 / 3'],
                ],
            ],
        ];
    }

    /**
     * @return array<string, array<string, string>>
     */
    public function getDeskMap(): array
    {
        $deskMap = [];

        foreach ($this->getRooms() as $room) {
            foreach ($room['desks'] as $desk) {
                $deskMap[$desk['id']] = [
                    'label' => $desk['label'],
                    'roomName' => $room['name'],
                    'roomId' => $room['id'],
                ];
            }
        }

        return $deskMap;
    }
}
