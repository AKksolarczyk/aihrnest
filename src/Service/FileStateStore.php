<?php

namespace App\Service;

final class FileStateStore
{
    private const DEFAULT_VACATION_DAYS = 26;

    /**
     * @var array<string, array<int, string>>
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

    /**
     * @return array<string, mixed>
     */
    public function load(): array
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

        $normalizedState = $this->normalizeState($data);

        if ($normalizedState !== $data) {
            $this->save($normalizedState);
        }

        return $normalizedState;
    }

    /**
     * @param array<string, mixed> $state
     */
    public function save(array $state): void
    {
        $path = $this->getPath();
        $directory = \dirname($path);

        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        file_put_contents($path, json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
    }

    private function getPath(): string
    {
        return $this->projectDir.'/var/data/app-state.json';
    }

    /**
     * @return array<string, mixed>
     */
    private function getDefaultState(): array
    {
        return [
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
        ];
    }

    /**
     * @param array<string, mixed> $state
     * @return array<string, mixed>
     */
    private function normalizeState(array $state): array
    {
        $state['users'] = array_map(function (array $user): array {
            $userId = $user['id'] ?? '';
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
}
