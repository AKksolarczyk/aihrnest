<?php

declare(strict_types=1);

namespace App\Workspace\Application\Query\GetDashboard;

use DateTimeImmutable;

final readonly class DashboardView
{
    /**
     * @param array<int, array<string, mixed>> $users
     * @param array<int, array<string, mixed>> $userStatuses
     * @param array<string, mixed>|null $activeUser
     * @param array<string, mixed>|null $activeUserStatus
     * @param array<int, array<string, mixed>> $rooms
     * @param array<int, array<string, mixed>> $weekOverview
     * @param list<array{id: string, label: string, roomName: string}> $availableDesks
     * @param array<int, array<string, string>> $vacations
     * @param array<int, array<string, string>> $deskClaims
     * @param array<string, int> $summary
     */
    public function __construct(
        public DateTimeImmutable $selectedDate,
        public ?array $activeUser,
        public ?array $activeUserStatus,
        public array $users,
        public array $userStatuses,
        public array $rooms,
        public array $weekOverview,
        public array $availableDesks,
        public array $vacations,
        public array $deskClaims,
        public array $summary,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'selectedDate' => $this->selectedDate,
            'activeUser' => $this->activeUser,
            'activeUserStatus' => $this->activeUserStatus,
            'users' => $this->users,
            'userStatuses' => $this->userStatuses,
            'rooms' => $this->rooms,
            'weekOverview' => $this->weekOverview,
            'availableDesks' => $this->availableDesks,
            'vacations' => $this->vacations,
            'deskClaims' => $this->deskClaims,
            'summary' => $this->summary,
        ];
    }
}
