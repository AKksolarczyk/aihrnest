<?php

declare(strict_types=1);

namespace App\Workspace\Infrastructure\Persistence;

use App\Workspace\Domain\Model\DeskClaim;
use App\Workspace\Domain\Model\User;
use App\Workspace\Domain\Model\Vacation;
use App\Workspace\Domain\Model\WorkspaceState;
use App\Workspace\Domain\Repository\WorkspaceStateRepositoryInterface;
use DateTimeImmutable;

final class FileWorkspaceStateRepository implements WorkspaceStateRepositoryInterface
{
    private const DEFAULT_VACATION_DAYS = 26;

    /**
     * @var array<string, list<string>>
     */
    private const DEFAULT_USER_SCHEDULES = [
        'u1' => ['monday', 'tuesday', 'thursday'],
        'u2' => ['monday', 'wednesday', 'friday'],
        'u3' => ['tuesday', 'thursday', 'friday'],
        'u4' => ['monday', 'wednesday', 'thursday'],
        'u5' => ['tuesday', 'wednesday', 'friday'],
    ];

    public function __construct(
        private readonly string $projectDir,
    ) {
    }

    public function load(): WorkspaceState
    {
        $path = $this->getPath();

        if (!is_file($path)) {
            $state = $this->getDefaultState();
            $this->save($state);

            return $state;
        }

        $json = file_get_contents($path);
        $data = is_string($json) ? json_decode($json, true) : null;

        if (!is_array($data)) {
            $state = $this->getDefaultState();
            $this->save($state);

            return $state;
        }

        $normalized = $this->normalizeState($data);
        $workspaceState = $this->hydrate($normalized);

        if ($normalized !== $data) {
            $this->save($workspaceState);
        }

        return $workspaceState;
    }

    public function save(WorkspaceState $workspaceState): void
    {
        $path = $this->getPath();
        $directory = dirname($path);

        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        file_put_contents($path, json_encode($this->extract($workspaceState), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
    }

    private function getPath(): string
    {
        return $this->projectDir.'/var/data/app-state.json';
    }

    private function getDefaultState(): WorkspaceState
    {
        return $this->hydrate([
            'users' => [
                [
                    'id' => 'u1',
                    'name' => 'Anna Kowalska',
                    'team' => 'Product',
                    'assignedDeskId' => 'A-01',
                    'schedule' => self::DEFAULT_USER_SCHEDULES['u1'],
                    'vacationDaysTotal' => self::DEFAULT_VACATION_DAYS,
                    'vacationDaysRemaining' => self::DEFAULT_VACATION_DAYS,
                ],
                [
                    'id' => 'u2',
                    'name' => 'Piotr Nowak',
                    'team' => 'Operations',
                    'assignedDeskId' => 'A-02',
                    'schedule' => self::DEFAULT_USER_SCHEDULES['u2'],
                    'vacationDaysTotal' => self::DEFAULT_VACATION_DAYS,
                    'vacationDaysRemaining' => self::DEFAULT_VACATION_DAYS,
                ],
                [
                    'id' => 'u3',
                    'name' => 'Marta Zielinska',
                    'team' => 'Sales',
                    'assignedDeskId' => 'B-01',
                    'schedule' => self::DEFAULT_USER_SCHEDULES['u3'],
                    'vacationDaysTotal' => self::DEFAULT_VACATION_DAYS,
                    'vacationDaysRemaining' => self::DEFAULT_VACATION_DAYS,
                ],
                [
                    'id' => 'u4',
                    'name' => 'Tomasz Wisniewski',
                    'team' => 'Engineering',
                    'assignedDeskId' => 'C-01',
                    'schedule' => self::DEFAULT_USER_SCHEDULES['u4'],
                    'vacationDaysTotal' => self::DEFAULT_VACATION_DAYS,
                    'vacationDaysRemaining' => self::DEFAULT_VACATION_DAYS,
                ],
                [
                    'id' => 'u5',
                    'name' => 'Julia Kaczmarek',
                    'team' => 'HR',
                    'assignedDeskId' => 'C-02',
                    'schedule' => self::DEFAULT_USER_SCHEDULES['u5'],
                    'vacationDaysTotal' => self::DEFAULT_VACATION_DAYS,
                    'vacationDaysRemaining' => self::DEFAULT_VACATION_DAYS,
                ],
            ],
            'vacations' => [],
            'deskClaims' => [],
        ]);
    }

    /**
     * @param array<string, mixed> $state
     * @return array<string, mixed>
     */
    private function normalizeState(array $state): array
    {
        $state['users'] = array_map(function (array $user): array {
            $userId = (string) ($user['id'] ?? '');
            $defaultSchedule = self::DEFAULT_USER_SCHEDULES[$userId] ?? ['monday', 'wednesday', 'friday'];
            $vacationDaysTotal = (int) ($user['vacationDaysTotal'] ?? self::DEFAULT_VACATION_DAYS);
            $vacationDaysRemaining = (int) ($user['vacationDaysRemaining'] ?? $vacationDaysTotal);

            $user['schedule'] = $defaultSchedule;
            $user['vacationDaysTotal'] = $vacationDaysTotal;
            $user['vacationDaysRemaining'] = max(0, min($vacationDaysRemaining, $vacationDaysTotal));

            return $user;
        }, $state['users'] ?? []);

        $state['vacations'] = array_values($state['vacations'] ?? []);
        $state['deskClaims'] = array_values($state['deskClaims'] ?? []);

        return $state;
    }

    /**
     * @param array<string, mixed> $state
     */
    private function hydrate(array $state): WorkspaceState
    {
        $users = array_map(static fn (array $user): User => new User(
            (string) $user['id'],
            (string) $user['name'],
            (string) $user['team'],
            (string) $user['assignedDeskId'],
            array_values($user['schedule']),
            (int) $user['vacationDaysTotal'],
            (int) $user['vacationDaysRemaining'],
        ), $state['users'] ?? []);

        $vacations = array_map(static fn (array $vacation): Vacation => new Vacation(
            (string) $vacation['id'],
            (string) $vacation['userId'],
            new DateTimeImmutable((string) $vacation['startDate']),
            new DateTimeImmutable((string) $vacation['endDate']),
        ), $state['vacations'] ?? []);

        $deskClaims = array_map(static fn (array $deskClaim): DeskClaim => new DeskClaim(
            (string) $deskClaim['id'],
            (string) $deskClaim['userId'],
            (string) $deskClaim['deskId'],
            new DateTimeImmutable((string) $deskClaim['date']),
        ), $state['deskClaims'] ?? []);

        return new WorkspaceState($users, $vacations, $deskClaims);
    }

    /**
     * @return array<string, mixed>
     */
    private function extract(WorkspaceState $workspaceState): array
    {
        return [
            'users' => array_map(static fn (User $user): array => [
                'id' => $user->id(),
                'name' => $user->name(),
                'team' => $user->team(),
                'assignedDeskId' => $user->assignedDeskId(),
                'schedule' => $user->schedule(),
                'vacationDaysTotal' => $user->vacationDaysTotal(),
                'vacationDaysRemaining' => $user->vacationDaysRemaining(),
            ], $workspaceState->users()),
            'vacations' => array_map(static fn (Vacation $vacation): array => [
                'id' => $vacation->id(),
                'userId' => $vacation->userId(),
                'startDate' => $vacation->startDate()->format('Y-m-d'),
                'endDate' => $vacation->endDate()->format('Y-m-d'),
            ], $workspaceState->vacations()),
            'deskClaims' => array_map(static fn (DeskClaim $deskClaim): array => [
                'id' => $deskClaim->id(),
                'userId' => $deskClaim->userId(),
                'deskId' => $deskClaim->deskId(),
                'date' => $deskClaim->date()->format('Y-m-d'),
            ], $workspaceState->deskClaims()),
        ];
    }
}
