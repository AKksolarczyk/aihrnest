<?php

declare(strict_types=1);

namespace App\Workspace\Application\Query\GetDashboard;

use App\Workspace\Domain\Model\Room;
use App\Workspace\Domain\Model\User;
use App\Workspace\Domain\Model\WorkspaceState;
use App\Workspace\Domain\Repository\OfficeLayoutRepositoryInterface;
use App\Workspace\Domain\Repository\WorkspaceStateRepositoryInterface;
use App\Workspace\Domain\Service\DailyPlan;
use App\Workspace\Domain\Service\WorkspacePlanner;
use DateInterval;

final class GetDashboardHandler
{
    public function __construct(
        private readonly WorkspaceStateRepositoryInterface $workspaceStateRepository,
        private readonly OfficeLayoutRepositoryInterface $officeLayoutRepository,
        private readonly WorkspacePlanner $workspacePlanner,
    ) {
    }

    public function handle(GetDashboardQuery $query): DashboardView
    {
        $workspaceState = $this->workspaceStateRepository->load();
        $users = $this->indexUsers($workspaceState);
        $rooms = $this->officeLayoutRepository->findAllRooms();
        $deskMap = $this->workspacePlanner->buildDeskMap($rooms);
        $activeUserId = isset($users[$query->activeUserId]) ? $query->activeUserId : (array_key_first($users) ?? '');
        $dailyPlan = $this->workspacePlanner->buildDailyPlan($query->selectedDate, $workspaceState, $rooms);
        $userStatuses = $this->buildUserStatuses($query->selectedDate, $users, $deskMap, $dailyPlan);

        return new DashboardView(
            $query->selectedDate,
            isset($users[$activeUserId]) ? $this->mapUser($users[$activeUserId]) : null,
            $this->findActiveUserStatus($userStatuses, $activeUserId),
            array_map(fn (User $user): array => $this->mapUser($user), array_values($users)),
            $userStatuses,
            $this->buildRoomsView($rooms, $dailyPlan, $deskMap),
            $this->buildWeekOverview($query->selectedDate, $workspaceState, $rooms),
            $dailyPlan->availableDesks(),
            $this->buildVacationsViewForUser($workspaceState, $activeUserId, $users),
            $this->buildDeskClaimsViewForUser($workspaceState, $activeUserId, $users, $deskMap),
            [
                'occupiedCount' => count($dailyPlan->occupancy()),
                'freeCount' => count($dailyPlan->availableDesks()),
                'vacationCount' => count(array_filter(
                    $userStatuses,
                    static fn (array $status): bool => $status['isOnVacation'],
                )),
            ],
        );
    }

    /**
     * @return array<string, User>
     */
    private function indexUsers(WorkspaceState $workspaceState): array
    {
        $indexed = [];

        foreach ($workspaceState->users() as $user) {
            $indexed[$user->id()] = $user;
        }

        uasort($indexed, static fn (User $left, User $right): int => strcmp($left->name(), $right->name()));

        return $indexed;
    }

    /**
     * @param array<string, User> $users
     * @param array<string, array{label: string, roomName: string, roomId: string}> $deskMap
     * @return array<int, array<string, mixed>>
     */
    private function buildUserStatuses(\DateTimeImmutable $date, array $users, array $deskMap, DailyPlan $dailyPlan): array
    {
        $statuses = [];

        foreach ($users as $userId => $user) {
            $deskId = $dailyPlan->userDeskMap()[$userId] ?? null;
            $isOnVacation = isset($dailyPlan->vacationUserIds()[$userId]);
            $isScheduled = $user->isScheduledOn($date);

            $statuses[] = [
                'id' => $userId,
                'name' => $user->name(),
                'team' => $user->team(),
                'assignedDeskLabel' => $deskMap[$user->assignedDeskId()]['label'] ?? $user->assignedDeskId(),
                'schedule' => $user->schedule(),
                'vacationDaysTotal' => $user->vacationDaysTotal(),
                'vacationDaysRemaining' => $user->vacationDaysRemaining(),
                'isOnVacation' => $isOnVacation,
                'isScheduledToday' => $isScheduled,
                'deskLabel' => $deskId ? ($deskMap[$deskId]['label'] ?? $deskId) : null,
                'statusLabel' => match (true) {
                    $isOnVacation => 'Urlop',
                    $deskId !== null && $deskId === $user->assignedDeskId() => 'Pracuje z przypisanego biurka',
                    $deskId !== null => 'Zajal wolne biurko',
                    default => 'Bez biurka w tym dniu',
                },
            ];
        }

        return $statuses;
    }

    /**
     * @param array<int, array<string, mixed>> $userStatuses
     * @return array<string, mixed>|null
     */
    private function findActiveUserStatus(array $userStatuses, string $activeUserId): ?array
    {
        foreach ($userStatuses as $status) {
            if ($status['id'] === $activeUserId) {
                return $status;
            }
        }

        return null;
    }

    /**
     * @param list<Room> $rooms
     * @param array<string, array{label: string, roomName: string, roomId: string}> $deskMap
     * @return array<int, array<string, mixed>>
     */
    private function buildRoomsView(array $rooms, DailyPlan $dailyPlan, array $deskMap): array
    {
        $view = [];

        foreach ($rooms as $room) {
            $desks = [];

            foreach ($room->desks() as $desk) {
                $occupancy = $dailyPlan->occupancy()[$desk->id()] ?? null;
                $desks[] = [
                    'id' => $desk->id(),
                    'label' => $deskMap[$desk->id()]['label'] ?? $desk->id(),
                    'occupancy' => $occupancy,
                    'isFree' => $occupancy === null,
                ];
            }

            $view[] = [
                'id' => $room->id(),
                'name' => $room->name(),
                'description' => $room->description(),
                'desks' => $desks,
            ];
        }

        return $view;
    }

    /**
     * @param list<Room> $rooms
     * @return array<int, array<string, mixed>>
     */
    private function buildWeekOverview(\DateTimeImmutable $selectedDate, WorkspaceState $workspaceState, array $rooms): array
    {
        $days = [];

        for ($offset = 0; $offset < 5; ++$offset) {
            $day = $selectedDate->add(new DateInterval(sprintf('P%dD', $offset)));
            $plan = $this->workspacePlanner->buildDailyPlan($day, $workspaceState, $rooms);
            $days[] = [
                'date' => $day,
                'occupiedCount' => count($plan->occupancy()),
                'freeCount' => count($plan->availableDesks()),
                'vacationCount' => count($plan->vacationUserIds()),
            ];
        }

        return $days;
    }

    /**
     * @param array<string, User> $users
     * @return array<int, array<string, string>>
     */
    private function buildVacationsViewForUser(WorkspaceState $workspaceState, string $userId, array $users): array
    {
        $vacations = array_values(array_filter(
            $workspaceState->vacations(),
            static fn ($vacation): bool => $vacation->userId() === $userId,
        ));

        usort($vacations, static fn ($left, $right): int => strcmp(
            $left->startDate()->format('Y-m-d'),
            $right->startDate()->format('Y-m-d'),
        ));

        return array_map(static function ($vacation) use ($users): array {
            return [
                'userName' => $users[$vacation->userId()]->name() ?? $vacation->userId(),
                'startDate' => $vacation->startDate()->format('Y-m-d'),
                'endDate' => $vacation->endDate()->format('Y-m-d'),
            ];
        }, $vacations);
    }

    /**
     * @param array<string, User> $users
     * @param array<string, array{label: string, roomName: string, roomId: string}> $deskMap
     * @return array<int, array<string, string>>
     */
    private function buildDeskClaimsViewForUser(WorkspaceState $workspaceState, string $userId, array $users, array $deskMap): array
    {
        $claims = array_values(array_filter(
            $workspaceState->deskClaims(),
            static fn ($claim): bool => $claim->userId() === $userId,
        ));

        usort($claims, static fn ($left, $right): int => strcmp(
            $left->date()->format('Y-m-d'),
            $right->date()->format('Y-m-d'),
        ));

        return array_map(static function ($claim) use ($users, $deskMap): array {
            return [
                'userName' => $users[$claim->userId()]->name() ?? $claim->userId(),
                'deskLabel' => $deskMap[$claim->deskId()]['label'] ?? $claim->deskId(),
                'date' => $claim->date()->format('Y-m-d'),
            ];
        }, $claims);
    }

    /**
     * @return array{id: string, name: string, team: string}
     */
    private function mapUser(User $user): array
    {
        return [
            'id' => $user->id(),
            'name' => $user->name(),
            'team' => $user->team(),
        ];
    }
}
