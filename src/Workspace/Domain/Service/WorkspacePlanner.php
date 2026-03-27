<?php

declare(strict_types=1);

namespace App\Workspace\Domain\Service;

use App\Workspace\Domain\Model\Room;
use App\Workspace\Domain\Model\WorkspaceState;
use DateTimeImmutable;

final class WorkspacePlanner
{
    /**
     * @param list<Room> $rooms
     */
    public function buildDailyPlan(DateTimeImmutable $date, WorkspaceState $workspaceState, array $rooms): DailyPlan
    {
        $users = $this->indexUsers($workspaceState);
        $deskMap = $this->buildDeskMap($rooms);
        $occupancy = [];
        $userDeskMap = [];
        $vacationUserIds = [];

        foreach ($users as $userId => $user) {
            if ($this->isOnVacation($userId, $date, $workspaceState)) {
                $vacationUserIds[$userId] = true;
                continue;
            }

            if ($user->isScheduledOn($date)) {
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

        foreach ($workspaceState->deskClaims() as $deskClaim) {
            if (!$deskClaim->matchesDate($date)) {
                continue;
            }

            $userId = $deskClaim->userId();
            $deskId = $deskClaim->deskId();
            $user = $users[$userId] ?? null;

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
     * @return array<string, \App\Workspace\Domain\Model\User>
     */
    private function indexUsers(WorkspaceState $workspaceState): array
    {
        $indexed = [];

        foreach ($workspaceState->users() as $user) {
            $indexed[$user->id()] = $user;
        }

        uasort($indexed, static fn ($left, $right): int => strcmp($left->name(), $right->name()));

        return $indexed;
    }

    private function isOnVacation(string $userId, DateTimeImmutable $date, WorkspaceState $workspaceState): bool
    {
        foreach ($workspaceState->vacations() as $vacation) {
            if ($vacation->userId() === $userId && $vacation->includes($date)) {
                return true;
            }
        }

        return false;
    }
}
