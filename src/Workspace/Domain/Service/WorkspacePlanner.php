<?php

declare(strict_types=1);

namespace App\Workspace\Domain\Service;

use App\Workspace\Domain\Model\DeskClaim;
use App\Workspace\Domain\Model\Room;
use App\Workspace\Domain\Model\User;
use App\Workspace\Domain\Model\Vacation;
use DateTimeImmutable;

final class WorkspacePlanner
{
    /**
     * @param list<User> $users
     * @param list<Vacation> $vacations
     * @param list<DeskClaim> $deskClaims
     * @param list<Room> $rooms
     */
    public function buildDailyPlan(DateTimeImmutable $date, array $users, array $vacations, array $deskClaims, array $rooms): DailyPlan
    {
        $usersById = $this->indexUsers($users);
        $deskMap = $this->buildDeskMap($rooms);
        $occupancy = [];
        $userDeskMap = [];
        $vacationUserIds = [];

        foreach ($usersById as $userId => $user) {
            if ($this->isOnVacation($userId, $date, $vacations)) {
                $vacationUserIds[$userId] = true;
                continue;
            }

            if ($user->isScheduledOn($date) && $user->hasAssignedDesk()) {
                $deskId = $user->assignedDeskId();
                $occupancy[$deskId] = [
                    'deskId' => $deskId,
                    'deskLabel' => $deskMap[$deskId]['label'] ?? $deskId,
                    'userId' => $userId,
                    'userName' => $user->name(),
                    'team' => $user->team(),
                    'type' => 'schedule',
                    'label' => 'stale biurko',
                ];
                $userDeskMap[$userId] = $deskId;
            }
        }

        foreach ($deskClaims as $deskClaim) {
            if (!$deskClaim->matchesDate($date)) {
                continue;
            }

            $userId = $deskClaim->userId();
            $deskId = $deskClaim->deskId();
            $user = $usersById[$userId] ?? null;

            if (
                isset($vacationUserIds[$userId])
                || isset($userDeskMap[$userId])
                || isset($occupancy[$deskId])
                || $user === null
            ) {
                continue;
            }

            $occupancy[$deskId] = [
                'deskId' => $deskId,
                'deskLabel' => $deskMap[$deskId]['label'] ?? $deskId,
                'userId' => $userId,
                'userName' => $user->name(),
                'team' => $user->team(),
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

        /** @var array<string, array{id: string, label: string, roomName: string}> $availableDeskMap */
        $availableDeskMap = array_column($availableDesks, null, 'id');

        return new DailyPlan($occupancy, $userDeskMap, $vacationUserIds, $availableDesks, $availableDeskMap);
    }

    /**
     * @param list<Room> $rooms
     * @return array<string, array{label: string, roomName: string, roomId: string}>
     */
    public function buildDeskMap(array $rooms): array
    {
        $deskMap = [];

        foreach ($rooms as $room) {
            foreach ($room->desks() as $desk) {
                $deskMap[$desk->id()] = [
                    'label' => $desk->label(),
                    'roomName' => $room->name(),
                    'roomId' => $room->id(),
                ];
            }
        }

        return $deskMap;
    }

    /**
     * @param list<User> $users
     * @return array<string, User>
     */
    private function indexUsers(array $users): array
    {
        $indexed = [];

        foreach ($users as $user) {
            $indexed[$user->id()] = $user;
        }

        uasort($indexed, static fn ($left, $right): int => strcmp($left->name(), $right->name()));

        return $indexed;
    }

    /**
     * @param list<Vacation> $vacations
     */
    private function isOnVacation(string $userId, DateTimeImmutable $date, array $vacations): bool
    {
        foreach ($vacations as $vacation) {
            if ($vacation->userId() === $userId && $vacation->includes($date)) {
                return true;
            }
        }

        return false;
    }
}
