<?php

namespace App\Service;

final class FileStateStore
{
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

        return $data;
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
                    'schedule' => ['monday', 'tuesday', 'thursday'],
                ],
                [
                    'id' => 'u2',
                    'name' => 'Piotr Nowak',
                    'team' => 'Operations',
                    'assignedDeskId' => 'A-02',
                    'schedule' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
                ],
                [
                    'id' => 'u3',
                    'name' => 'Marta Zielinska',
                    'team' => 'Sales',
                    'assignedDeskId' => 'B-01',
                    'schedule' => ['wednesday', 'thursday', 'friday'],
                ],
                [
                    'id' => 'u4',
                    'name' => 'Tomasz Wisniewski',
                    'team' => 'Engineering',
                    'assignedDeskId' => 'C-01',
                    'schedule' => ['tuesday', 'wednesday'],
                ],
                [
                    'id' => 'u5',
                    'name' => 'Julia Kaczmarek',
                    'team' => 'HR',
                    'assignedDeskId' => 'C-02',
                    'schedule' => ['monday', 'thursday'],
                ],
            ],
            'vacations' => [
                [
                    'id' => 'vac-1',
                    'userId' => 'u3',
                    'startDate' => '2026-03-30',
                    'endDate' => '2026-04-01',
                ],
            ],
            'deskClaims' => [
                [
                    'id' => 'claim-1',
                    'userId' => 'u5',
                    'deskId' => 'B-04',
                    'date' => '2026-03-31',
                ],
            ],
        ];
    }
}
