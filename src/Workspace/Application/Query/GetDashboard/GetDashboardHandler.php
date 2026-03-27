<?php

declare(strict_types=1);

namespace App\Workspace\Application\Query\GetDashboard;

use App\Workspace\Domain\Model\DeskClaim;
use App\Workspace\Domain\Model\DeskWaitlistEntry;
use App\Workspace\Domain\Model\IssueReport;
use App\Workspace\Domain\Model\RecurringDeskReservation;
use App\Workspace\Domain\Model\Room;
use App\Workspace\Domain\Model\User;
use App\Workspace\Domain\Model\Vacation;
use App\Workspace\Domain\Repository\DeskClaimRepositoryInterface;
use App\Workspace\Domain\Repository\DeskWaitlistRepositoryInterface;
use App\Workspace\Domain\Repository\IssueReportRepositoryInterface;
use App\Workspace\Domain\Repository\OfficeLayoutRepositoryInterface;
use App\Workspace\Domain\Repository\RecurringDeskReservationRepositoryInterface;
use App\Workspace\Domain\Repository\UserRepositoryInterface;
use App\Workspace\Domain\Repository\VacationRepositoryInterface;
use App\Workspace\Domain\Service\DailyPlan;
use App\Workspace\Domain\Service\WorkspacePlanner;
use DateInterval;
use Symfony\Contracts\Translation\TranslatorInterface;

final class GetDashboardHandler
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly VacationRepositoryInterface $vacationRepository,
        private readonly DeskClaimRepositoryInterface $deskClaimRepository,
        private readonly DeskWaitlistRepositoryInterface $deskWaitlistRepository,
        private readonly RecurringDeskReservationRepositoryInterface $recurringDeskReservationRepository,
        private readonly IssueReportRepositoryInterface $issueReportRepository,
        private readonly OfficeLayoutRepositoryInterface $officeLayoutRepository,
        private readonly WorkspacePlanner $workspacePlanner,
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function handle(GetDashboardQuery $query): DashboardView
    {
        $users = $this->indexUsers($this->userRepository->findAllOrderedByName());
        $vacations = $this->vacationRepository->findAll();
        $deskClaims = $this->deskClaimRepository->findAll();
        $deskWaitlistEntries = $this->deskWaitlistRepository->findAll();
        $recurringReservations = $this->recurringDeskReservationRepository->findAll();
        $issueReports = $this->issueReportRepository->findAll();
        $rooms = $this->officeLayoutRepository->findAllRooms();
        $deskMap = $this->workspacePlanner->buildDeskMap($rooms);
        $activeUserId = isset($users[$query->activeUserId]) ? $query->activeUserId : (array_key_first($users) ?? '');
        $dailyPlan = $this->workspacePlanner->buildDailyPlan($query->selectedDate, array_values($users), $vacations, $deskClaims, $rooms);
        $userStatuses = $this->buildUserStatuses($query->selectedDate, $users, $deskMap, $dailyPlan);

        return new DashboardView(
            $query->selectedDate,
            isset($users[$activeUserId]) ? $this->mapUser($users[$activeUserId]) : null,
            $this->findActiveUserStatus($userStatuses, $activeUserId),
            array_map(fn (User $user): array => $this->mapUser($user), array_values($users)),
            $userStatuses,
            $this->buildRoomsView($rooms, $dailyPlan, $deskMap),
            $this->buildWeekOverview($query->selectedDate, array_values($users), $vacations, $deskClaims, $rooms),
            $dailyPlan->availableDesks(),
            $this->buildVacationsViewForUser($vacations, $activeUserId, $users),
            $this->buildDeskClaimsViewForUser($deskClaims, $activeUserId, $users, $deskMap),
            $this->buildDeskCatalog($deskMap),
            $this->buildRoomCatalog($rooms),
            $this->buildWaitlistViewForUser($deskWaitlistEntries, $activeUserId, $deskMap),
            $this->buildRecurringReservationsViewForUser($recurringReservations, $activeUserId, $deskMap),
            $this->buildIssueReportsViewForUser($issueReports, $activeUserId, $deskMap, $rooms),
            $this->buildPeopleFinderView($userStatuses),
            $this->buildAdminReports($deskClaims, $deskWaitlistEntries, $issueReports, $recurringReservations, $deskMap),
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
    private function indexUsers(array $users): array
    {
        $indexed = [];

        foreach ($users as $user) {
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
                'email' => $user->email(),
                'hrnestEmployeeId' => $user->hrnestEmployeeId(),
                'role' => $this->resolveUserRole($user),
                'team' => $user->team(),
                'assignedDeskLabel' => $user->assignedDeskId() ? ($deskMap[$user->assignedDeskId()]['label'] ?? $user->assignedDeskId()) : $this->translator->trans('common.none'),
                'schedule' => $user->schedule(),
                'vacationDaysTotal' => $user->vacationDaysTotal(),
                'vacationDaysRemaining' => $user->vacationDaysRemaining(),
                'isOnVacation' => $isOnVacation,
                'isScheduledToday' => $isScheduled,
                'deskLabel' => $deskId ? ($deskMap[$deskId]['label'] ?? $deskId) : null,
                'occupancyType' => $deskId !== null ? ($dailyPlan->occupancy()[$deskId]['type'] ?? null) : null,
                'statusLabel' => match (true) {
                    $isOnVacation => $this->translator->trans('dashboard.status.vacation'),
                    $deskId !== null && $user->assignedDeskId() !== null && $deskId === $user->assignedDeskId() => $this->translator->trans('dashboard.status.assigned_desk'),
                    $deskId !== null => $this->translator->trans('dashboard.status.claimed_desk'),
                    $isScheduled && !$user->hasAssignedDesk() => $this->translator->trans('dashboard.status.no_assigned_desk'),
                    default => $this->translator->trans('dashboard.status.no_desk'),
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
        $roomLayouts = $this->roomLayouts();

        foreach ($rooms as $room) {
            $desks = [];
            $layout = $roomLayouts[$room->id()] ?? [
                'width' => 2,
                'height' => 2,
                'desks' => [],
            ];

            foreach ($room->desks() as $desk) {
                $occupancy = $dailyPlan->occupancy()[$desk->id()] ?? null;
                $position = $layout['desks'][$desk->id()] ?? ['x' => 1, 'y' => 1];
                $desks[] = [
                    'id' => $desk->id(),
                    'label' => $deskMap[$desk->id()]['label'] ?? $desk->id(),
                'occupancy' => $occupancy,
                'isFree' => $occupancy === null,
                'position' => $position,
            ];

            if ($occupancy !== null) {
                $desks[array_key_last($desks)]['occupancy']['label'] = $this->translateOccupancyLabel((string) $occupancy['type']);
            }
        }

            $view[] = [
                'id' => $room->id(),
                'name' => $room->name(),
                'description' => $room->description(),
                'map' => [
                    'width' => $layout['width'],
                    'height' => $layout['height'],
                ],
                'desks' => $desks,
            ];
        }

        return $view;
    }

    /**
     * @param list<Room> $rooms
     * @return array<int, array<string, mixed>>
     */
    private function buildWeekOverview(\DateTimeImmutable $selectedDate, array $users, array $vacations, array $deskClaims, array $rooms): array
    {
        $days = [];

        for ($offset = 0; $offset < 5; ++$offset) {
            $day = $selectedDate->add(new DateInterval(sprintf('P%dD', $offset)));
            $plan = $this->workspacePlanner->buildDailyPlan($day, $users, $vacations, $deskClaims, $rooms);
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
    private function buildVacationsViewForUser(array $vacations, string $userId, array $users): array
    {
        $vacations = array_values(array_filter(
            $vacations,
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
    private function buildDeskClaimsViewForUser(array $deskClaims, string $userId, array $users, array $deskMap): array
    {
        $claims = array_values(array_filter(
            $deskClaims,
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
     * @param array<string, array{label: string, roomName: string, roomId: string}> $deskMap
     * @return list<array{id: string, label: string, roomName: string}>
     */
    private function buildDeskCatalog(array $deskMap): array
    {
        $catalog = [];

        foreach ($deskMap as $deskId => $desk) {
            $catalog[] = [
                'id' => $deskId,
                'label' => $desk['label'],
                'roomName' => $desk['roomName'],
            ];
        }

        usort($catalog, static fn (array $left, array $right): int => strcmp($left['label'], $right['label']));

        return $catalog;
    }

    /**
     * @param list<Room> $rooms
     * @return list<array{id: string, name: string}>
     */
    private function buildRoomCatalog(array $rooms): array
    {
        return array_map(static fn (Room $room): array => [
            'id' => $room->id(),
            'name' => $room->name(),
        ], $rooms);
    }

    /**
     * @param list<DeskWaitlistEntry> $entries
     * @param array<string, array{label: string, roomName: string, roomId: string}> $deskMap
     * @return array<int, array<string, string>>
     */
    private function buildWaitlistViewForUser(array $entries, string $userId, array $deskMap): array
    {
        $entries = array_values(array_filter(
            $entries,
            static fn (DeskWaitlistEntry $entry): bool => $entry->userId() === $userId,
        ));

        usort($entries, static fn (DeskWaitlistEntry $left, DeskWaitlistEntry $right): int => strcmp(
            $left->date()->format('Y-m-d'),
            $right->date()->format('Y-m-d'),
        ));

        return array_map(static function (DeskWaitlistEntry $entry) use ($deskMap): array {
            return [
                'deskLabel' => $deskMap[$entry->deskId()]['label'] ?? $entry->deskId(),
                'roomName' => $deskMap[$entry->deskId()]['roomName'] ?? '',
                'date' => $entry->date()->format('Y-m-d'),
                'status' => $entry->status(),
            ];
        }, $entries);
    }

    /**
     * @param list<RecurringDeskReservation> $reservations
     * @param array<string, array{label: string, roomName: string, roomId: string}> $deskMap
     * @return array<int, array<string, string|array<int, string>>>
     */
    private function buildRecurringReservationsViewForUser(array $reservations, string $userId, array $deskMap): array
    {
        $reservations = array_values(array_filter(
            $reservations,
            static fn (RecurringDeskReservation $reservation): bool => $reservation->userId() === $userId,
        ));

        return array_map(static function (RecurringDeskReservation $reservation) use ($deskMap): array {
            return [
                'deskLabel' => $deskMap[$reservation->deskId()]['label'] ?? $reservation->deskId(),
                'roomName' => $deskMap[$reservation->deskId()]['roomName'] ?? '',
                'startDate' => $reservation->startDate()->format('Y-m-d'),
                'endDate' => $reservation->endDate()->format('Y-m-d'),
                'weekdays' => $reservation->weekdays(),
            ];
        }, $reservations);
    }

    /**
     * @param list<IssueReport> $issues
     * @param list<Room> $rooms
     * @param array<string, array{label: string, roomName: string, roomId: string}> $deskMap
     * @return array<int, array<string, string>>
     */
    private function buildIssueReportsViewForUser(array $issues, string $userId, array $deskMap, array $rooms): array
    {
        $roomNames = [];

        foreach ($rooms as $room) {
            $roomNames[$room->id()] = $room->name();
        }

        $issues = array_values(array_filter(
            $issues,
            static fn (IssueReport $issue): bool => $issue->userId() === $userId,
        ));

        return array_map(static function (IssueReport $issue) use ($deskMap, $roomNames): array {
            return [
                'category' => $issue->category(),
                'status' => $issue->status(),
                'target' => $issue->deskId() !== null
                    ? (($deskMap[$issue->deskId()]['label'] ?? $issue->deskId()).' / '.($deskMap[$issue->deskId()]['roomName'] ?? ''))
                    : ($roomNames[$issue->roomId() ?? ''] ?? ($issue->roomId() ?? '')),
                'description' => $issue->description(),
                'reportedAt' => $issue->reportedAt()->format('Y-m-d H:i'),
            ];
        }, $issues);
    }

    /**
     * @param array<int, array<string, mixed>> $userStatuses
     * @return array<int, array<string, string>>
     */
    private function buildPeopleFinderView(array $userStatuses): array
    {
        $people = [];

        foreach ($userStatuses as $status) {
            if ($status['deskLabel'] === null && !$status['isOnVacation']) {
                continue;
            }

            $people[] = [
                'id' => (string) $status['id'],
                'name' => (string) $status['name'],
                'email' => (string) $status['email'],
                'hrnestEmployeeId' => is_string($status['hrnestEmployeeId'] ?? null) ? $status['hrnestEmployeeId'] : null,
                'team' => (string) $status['team'],
                'deskLabel' => (string) ($status['deskLabel'] ?? $this->translator->trans('dashboard.people.no_desk')),
                'statusLabel' => (string) $status['statusLabel'],
                'statusVariant' => match (true) {
                    (bool) $status['isOnVacation'] => 'vacation',
                    (string) ($status['occupancyType'] ?? '') === 'schedule' => 'schedule',
                    (string) ($status['occupancyType'] ?? '') === 'claim' => 'claim',
                    default => 'none',
                },
            ];
        }

        return $people;
    }

    /**
     * @param list<DeskClaim> $deskClaims
     * @param list<DeskWaitlistEntry> $waitlistEntries
     * @param list<IssueReport> $issueReports
     * @param list<RecurringDeskReservation> $recurringReservations
     * @param array<string, array{label: string, roomName: string, roomId: string}> $deskMap
     * @return array<string, mixed>
     */
    private function buildAdminReports(
        array $deskClaims,
        array $waitlistEntries,
        array $issueReports,
        array $recurringReservations,
        array $deskMap,
    ): array {
        $deskUsage = [];
        $waitlistUsage = [];
        $issueCategories = [];

        foreach ($deskClaims as $claim) {
            $deskUsage[$claim->deskId()] = ($deskUsage[$claim->deskId()] ?? 0) + 1;
        }

        foreach ($waitlistEntries as $entry) {
            if ($entry->status() !== 'waiting') {
                continue;
            }

            $waitlistUsage[$entry->deskId()] = ($waitlistUsage[$entry->deskId()] ?? 0) + 1;
        }

        foreach ($issueReports as $issue) {
            $issueCategories[$issue->category()] = ($issueCategories[$issue->category()] ?? 0) + 1;
        }

        arsort($deskUsage);
        arsort($waitlistUsage);
        arsort($issueCategories);

        return [
            'openIssueCount' => count(array_filter($issueReports, static fn (IssueReport $issue): bool => $issue->status() === 'open')),
            'waitingCount' => count(array_filter($waitlistEntries, static fn (DeskWaitlistEntry $entry): bool => $entry->status() === 'waiting')),
            'recurringReservationCount' => count(array_filter($recurringReservations, static fn (RecurringDeskReservation $reservation): bool => $reservation->isActive())),
            'topBusyDesks' => $this->mapDeskCounterReport($deskUsage, $deskMap),
            'topWaitlistedDesks' => $this->mapDeskCounterReport($waitlistUsage, $deskMap),
            'issuesByCategory' => $this->mapSimpleCounterReport($issueCategories),
        ];
    }

    /**
     * @param array<string, int> $counters
     * @param array<string, array{label: string, roomName: string, roomId: string}> $deskMap
     * @return array<int, array<string, string|int>>
     */
    private function mapDeskCounterReport(array $counters, array $deskMap): array
    {
        $items = [];

        foreach (array_slice($counters, 0, 5, true) as $deskId => $count) {
            $items[] = [
                'label' => $deskMap[$deskId]['label'] ?? $deskId,
                'roomName' => $deskMap[$deskId]['roomName'] ?? '',
                'count' => $count,
            ];
        }

        return $items;
    }

    /**
     * @param array<string, int> $counters
     * @return array<int, array<string, string|int>>
     */
    private function mapSimpleCounterReport(array $counters): array
    {
        $items = [];

        foreach (array_slice($counters, 0, 5, true) as $label => $count) {
            $items[] = [
                'label' => $this->translateIssueCategory($label),
                'count' => $count,
            ];
        }

        return $items;
    }

    /**
     * @return array{id: string, name: string, role: string, team: string}
     */
    private function mapUser(User $user): array
    {
        return [
            'id' => $user->id(),
            'name' => $user->name(),
            'role' => $this->resolveUserRole($user),
            'team' => $user->team(),
        ];
    }

    private function resolveUserRole(User $user): string
    {
        return in_array('ROLE_ADMIN', $user->getRoles(), true) ? 'admin' : 'user';
    }

    private function translateOccupancyLabel(string $type): string
    {
        return match ($type) {
            'schedule' => $this->translator->trans('dashboard.occupancy.schedule'),
            'claim' => $this->translator->trans('dashboard.occupancy.claim'),
            default => $type,
        };
    }

    private function translateIssueCategory(string $category): string
    {
        return $this->translator->trans(sprintf('dashboard.issue_category.%s', $category));
    }

    /**
     * @return array<string, array{width: int, height: int, desks: array<string, array{x: int, y: int}>}>
     */
    private function roomLayouts(): array
    {
        return [
            'focus-room' => [
                'width' => 5,
                'height' => 4,
                'desks' => [
                    'A-01' => ['x' => 1, 'y' => 1],
                    'A-02' => ['x' => 2, 'y' => 1],
                    'A-03' => ['x' => 4, 'y' => 1],
                    'A-04' => ['x' => 5, 'y' => 1],
                    'A-05' => ['x' => 1, 'y' => 2],
                    'A-06' => ['x' => 2, 'y' => 2],
                    'A-07' => ['x' => 4, 'y' => 2],
                    'A-08' => ['x' => 5, 'y' => 2],
                    'A-09' => ['x' => 1, 'y' => 4],
                    'A-10' => ['x' => 5, 'y' => 3],
                    'A-11' => ['x' => 5, 'y' => 4],
                ],
            ],
            'client-room' => [
                'width' => 6,
                'height' => 2,
                'desks' => [
                    'B-01' => ['x' => 1, 'y' => 1],
                    'B-02' => ['x' => 2, 'y' => 1],
                    'B-03' => ['x' => 3, 'y' => 1],
                    'B-04' => ['x' => 5, 'y' => 1],
                    'B-05' => ['x' => 6, 'y' => 1],
                    'B-06' => ['x' => 1, 'y' => 2],
                    'B-07' => ['x' => 2, 'y' => 2],
                    'B-08' => ['x' => 3, 'y' => 2],
                    'B-09' => ['x' => 5, 'y' => 2],
                    'B-10' => ['x' => 6, 'y' => 2],
                ],
            ],
            'makers-room' => [
                'width' => 6,
                'height' => 2,
                'desks' => [
                    'C-01' => ['x' => 1, 'y' => 1],
                    'C-02' => ['x' => 2, 'y' => 1],
                    'C-03' => ['x' => 3, 'y' => 1],
                    'C-04' => ['x' => 5, 'y' => 1],
                    'C-05' => ['x' => 6, 'y' => 1],
                    'C-06' => ['x' => 1, 'y' => 2],
                    'C-07' => ['x' => 2, 'y' => 2],
                    'C-08' => ['x' => 3, 'y' => 2],
                    'C-09' => ['x' => 5, 'y' => 2],
                    'C-10' => ['x' => 6, 'y' => 2],
                ],
            ],
        ];
    }
}
