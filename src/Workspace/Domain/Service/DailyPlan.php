<?php

declare(strict_types=1);

namespace App\Workspace\Domain\Service;

final class DailyPlan
{
    /**
     * @param array<string, array<string, string>> $occupancy
     * @param array<string, string> $userDeskMap
     * @param array<string, bool> $vacationUserIds
     * @param list<array{id: string, label: string, roomName: string}> $availableDesks
     * @param array<string, array{id: string, label: string, roomName: string}> $availableDeskMap
     */
    public function __construct(
        private array $occupancy,
        private array $userDeskMap,
        private array $vacationUserIds,
        private array $availableDesks,
        private array $availableDeskMap,
    ) {
    }

    /**
     * @return array<string, array<string, string>>
     */
    public function occupancy(): array
    {
        return $this->occupancy;
    }

    /**
     * @return array<string, string>
     */
    public function userDeskMap(): array
    {
        return $this->userDeskMap;
    }

    /**
     * @return array<string, bool>
     */
    public function vacationUserIds(): array
    {
        return $this->vacationUserIds;
    }

    /**
     * @return list<array{id: string, label: string, roomName: string}>
     */
    public function availableDesks(): array
    {
        return $this->availableDesks;
    }

    public function userHasDesk(string $userId): bool
    {
        return isset($this->userDeskMap[$userId]);
    }

    public function deskIsAvailable(string $deskId): bool
    {
        return isset($this->availableDeskMap[$deskId]);
    }
}
