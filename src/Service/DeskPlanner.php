<?php

namespace App\Service;

use DateInterval;
use DateTimeImmutable;
use InvalidArgumentException;

final class DeskPlanner
{
    public function __construct(
        private readonly FileStateStore $stateStore,
        private readonly OfficeLayoutProvider $layoutProvider,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getDashboardData(DateTimeImmutable $selectedDate, string $activeUserId): array
    {
        $state = $this->stateStore->load();
        $users = $this->indexUsers($state['users'] ?? []);
        $deskMap = $this->layoutProvider->getDeskMap();

        if (!isset($users[$activeUserId])) {
            $activeUserId = array_key_first($users) ?? '';
        }

        $dailyPlan = $this->buildDailyPlan($selectedDate, $state, $deskMap);
        $userStatuses = $this->buildUserStatuses($selectedDate, $users, $deskMap, $dailyPlan);
        $rooms = $this->buildRoomsView($this->layoutProvider->getRooms(), $dailyPlan, $deskMap);
        $weekOverview = $this->buildWeekOverview($selectedDate, $state, $deskMap);

        return [
            'selectedDate' => $selectedDate,
            'activeUser' => $users[$activeUserId] ?? null,
            'users' => array_values($users),
            'rooms' => $rooms,
            'weekOverview' => $weekOverview,
            'userStatuses' => $userStatuses,
            'availableDesks' => $dailyPlan['availableDesks'],
            'vacations' => $this->buildVacationsView($state['vacations'] ?? [], $users),
            'deskClaims' => $this->buildDeskClaimsView($state['deskClaims'] ?? [], $users, $deskMap),
            'summary' => [
                'occupiedCount' => count($dailyPlan['occupancy']),
                'freeCount' => count($dailyPlan['availableDesks']),
                'vacationCount' => count(array_filter(
                    $userStatuses,
                    static fn (array $status): bool => $status['isOnVacation']
                )),
            ],
        ];
    }

    public function requestVacation(string $userId, DateTimeImmutable $startDate, DateTimeImmutable $endDate): void
    {
        if ($endDate < $startDate) {
            throw new InvalidArgumentException('Data koncowa urlopu nie moze byc wczesniejsza niz poczatkowa.');
        }

        $state = $this->stateStore->load();
        $users = $this->indexUsers($state['users'] ?? []);

        if (!isset($users[$userId])) {
            throw new InvalidArgumentException('Nie znaleziono wskazanego uzytkownika.');
        }

        $state['vacations'][] = [
            'id' => sprintf('vac-%s', bin2hex(random_bytes(4))),
            'userId' => $userId,
            'startDate' => $startDate->format('Y-m-d'),
            'endDate' => $endDate->format('Y-m-d'),
        ];

        $this->stateStore->save($state);
    }

    public function claimDesk(string $userId, string $deskId, DateTimeImmutable $date): void
    {
        $state = $this->stateStore->load();
        $users = $this->indexUsers($state['users'] ?? []);
        $deskMap = $this->layoutProvider->getDeskMap();

        if (!isset($users[$userId])) {
            throw new InvalidArgumentException('Nie znaleziono wskazanego uzytkownika.');
        }

        if (!isset($deskMap[$deskId])) {
            throw new InvalidArgumentException('Wybrane biurko nie istnieje.');
        }

        $dailyPlan = $this->buildDailyPlan($date, $state, $deskMap);

        if (isset($dailyPlan['userDeskMap'][$userId])) {
            throw new InvalidArgumentException('Ten uzytkownik ma juz przydzielone biurko w wybranym dniu.');
        }

        if (!isset($dailyPlan['availableDeskMap'][$deskId])) {
            throw new InvalidArgumentException('Wybrane biurko nie jest wolne.');
        }

        $state['deskClaims'][] = [
            'id' => sprintf('claim-%s', bin2hex(random_bytes(4))),
            'userId' => $userId,
            'deskId' => $deskId,
            'date' => $date->format('Y-m-d'),
        ];

        $this->stateStore->save($state);
    }

    /**
     * @param array<int, array<string, mixed>> $users
     * @return array<string, array<string, mixed>>
     */
    private function indexUsers(array $users): array
    {
        $indexed = [];

        foreach ($users as $user) {
            $indexed[$user['id']] = $user;
        }

        uasort($indexed, static fn (array $left, array $right): int => strcmp($left['name'], $right['name']));

        return $indexed;
    }

    /**
     * @param array<string, mixed> $state
     * @param array<string, array<string, string>> $deskMap
     * @return array<string, mixed>
     */
    private function buildDailyPlan(DateTimeImmutable $date, array $state, array $deskMap): array
    {
        $users = $this->indexUsers($state['users'] ?? []);
        $occupancy = [];
        $userDeskMap = [];
        $vacationUserIds = [];

        foreach ($users as $userId => $user) {
            if ($this->isOnVacation($userId, $date, $state['vacations'] ?? [])) {
                $vacationUserIds[$userId] = true;
                continue;
            }

            if ($this->isScheduledForDate($user, $date)) {
                $deskId = $user['assignedDeskId'];
                $occupancy[$deskId] = [
                    'deskId' => $deskId,
                    'deskLabel' => $deskMap[$deskId]['label'] ?? $deskId,
                    'userId' => $userId,
                    'userName' => $user['name'],
                    'team' => $user['team'],
                    'type' => 'schedule',
                    'label' => 'stale biurko',
                ];
                $userDeskMap[$userId] = $deskId;
            }
        }

        foreach ($state['deskClaims'] ?? [] as $claim) {
            if ($claim['date'] !== $date->format('Y-m-d')) {
                continue;
            }

            $userId = $claim['userId'];
            $deskId = $claim['deskId'];

            if (isset($vacationUserIds[$userId]) || isset($userDeskMap[$userId]) || isset($occupancy[$deskId]) || !isset($users[$userId])) {
                continue;
            }

            $occupancy[$deskId] = [
                'deskId' => $deskId,
                'deskLabel' => $deskMap[$deskId]['label'] ?? $deskId,
                'userId' => $userId,
                'userName' => $users[$userId]['name'],
                'team' => $users[$userId]['team'],
                'type' => 'claim',
                'label' => 'zajete z puli wolnych',
            ];
            $userDeskMap[$userId] = $deskId;
        }

        $availableDesks = [];

        foreach ($deskMap as $deskId => $desk) {
            if (!isset($occupancy[$deskId])) {
                $availableDesks[] = [
                    'id' => $deskId,
                    'label' => $desk['label'],
                    'roomName' => $desk['roomName'],
                ];
            }
        }

        usort($availableDesks, static fn (array $left, array $right): int => strcmp($left['id'], $right['id']));

        return [
            'occupancy' => $occupancy,
            'userDeskMap' => $userDeskMap,
            'vacationUserIds' => $vacationUserIds,
            'availableDesks' => $availableDesks,
            'availableDeskMap' => array_column($availableDesks, null, 'id'),
        ];
    }

    /**
     * @param array<string, array<string, mixed>> $users
     * @param array<string, array<string, string>> $deskMap
     * @param array<string, mixed> $dailyPlan
     * @return array<int, array<string, mixed>>
     */
    private function buildUserStatuses(
        DateTimeImmutable $date,
        array $users,
        array $deskMap,
        array $dailyPlan,
    ): array {
        $statuses = [];

        foreach ($users as $userId => $user) {
            $deskId = $dailyPlan['userDeskMap'][$userId] ?? null;
            $isOnVacation = isset($dailyPlan['vacationUserIds'][$userId]);
            $isScheduled = $this->isScheduledForDate($user, $date);

            $statuses[] = [
                'id' => $userId,
                'name' => $user['name'],
                'team' => $user['team'],
                'assignedDeskLabel' => $deskMap[$user['assignedDeskId']]['label'] ?? $user['assignedDeskId'],
                'schedule' => $user['schedule'],
                'isOnVacation' => $isOnVacation,
                'isScheduledToday' => $isScheduled,
                'deskLabel' => $deskId ? ($deskMap[$deskId]['label'] ?? $deskId) : null,
                'statusLabel' => match (true) {
                    $isOnVacation => 'Urlop',
                    $deskId !== null && $deskId === $user['assignedDeskId'] => 'Pracuje z przypisanego biurka',
                    $deskId !== null => 'Zajal wolne biurko',
                    default => 'Bez biurka w tym dniu',
                },
            ];
        }

        return $statuses;
    }

    /**
     * @param array<int, array<string, mixed>> $rooms
     * @param array<string, mixed> $dailyPlan
     * @param array<string, array<string, string>> $deskMap
     * @return array<int, array<string, mixed>>
     */
    private function buildRoomsView(array $rooms, array $dailyPlan, array $deskMap): array
    {
        $view = [];

        foreach ($rooms as $room) {
            $desks = [];

            foreach ($room['desks'] as $desk) {
                $occupancy = $dailyPlan['occupancy'][$desk['id']] ?? null;
                $desks[] = [
                    'id' => $desk['id'],
                    'label' => $deskMap[$desk['id']]['label'] ?? $desk['id'],
                    'occupancy' => $occupancy,
                    'isFree' => $occupancy === null,
                ];
            }

            $view[] = [
                'id' => $room['id'],
                'name' => $room['name'],
                'description' => $room['description'],
                'desks' => $desks,
            ];
        }

        return $view;
    }

    /**
     * @param array<string, mixed> $state
     * @param array<string, array<string, string>> $deskMap
     * @return array<int, array<string, mixed>>
     */
    private function buildWeekOverview(DateTimeImmutable $selectedDate, array $state, array $deskMap): array
    {
        $days = [];

        for ($offset = 0; $offset < 5; ++$offset) {
            $day = $selectedDate->add(new DateInterval(sprintf('P%dD', $offset)));
            $plan = $this->buildDailyPlan($day, $state, $deskMap);
            $days[] = [
                'date' => $day,
                'occupiedCount' => count($plan['occupancy']),
                'freeCount' => count($plan['availableDesks']),
                'vacationCount' => count($plan['vacationUserIds']),
            ];
        }

        return $days;
    }

    /**
     * @param array<int, array<string, mixed>> $vacations
     * @param array<string, array<string, mixed>> $users
     * @return array<int, array<string, string>>
     */
    private function buildVacationsView(array $vacations, array $users): array
    {
        usort($vacations, static fn (array $left, array $right): int => strcmp($left['startDate'], $right['startDate']));

        return array_map(static function (array $vacation) use ($users): array {
            return [
                'userName' => $users[$vacation['userId']]['name'] ?? $vacation['userId'],
                'startDate' => $vacation['startDate'],
                'endDate' => $vacation['endDate'],
            ];
        }, $vacations);
    }

    /**
     * @param array<int, array<string, mixed>> $claims
     * @param array<string, array<string, mixed>> $users
     * @param array<string, array<string, string>> $deskMap
     * @return array<int, array<string, string>>
     */
    private function buildDeskClaimsView(array $claims, array $users, array $deskMap): array
    {
        usort($claims, static fn (array $left, array $right): int => strcmp($left['date'], $right['date']));

        return array_map(static function (array $claim) use ($users, $deskMap): array {
            return [
                'userName' => $users[$claim['userId']]['name'] ?? $claim['userId'],
                'deskLabel' => $deskMap[$claim['deskId']]['label'] ?? $claim['deskId'],
                'date' => $claim['date'],
            ];
        }, $claims);
    }

    /**
     * @param array<string, mixed> $user
     */
    private function isScheduledForDate(array $user, DateTimeImmutable $date): bool
    {
        return in_array(strtolower($date->format('l')), $user['schedule'], true);
    }

    /**
     * @param array<int, array<string, mixed>> $vacations
     */
    private function isOnVacation(string $userId, DateTimeImmutable $date, array $vacations): bool
    {
        $current = $date->format('Y-m-d');

        foreach ($vacations as $vacation) {
            if (
                $vacation['userId'] === $userId
                && $vacation['startDate'] <= $current
                && $vacation['endDate'] >= $current
            ) {
                return true;
            }
        }

        return false;
    }
}
