<?php

declare(strict_types=1);

namespace App\Workspace\Application\Command\ImportHrnestPeople;

use App\Workspace\Domain\Model\User;
use App\Workspace\Domain\Repository\OfficeLayoutRepositoryInterface;
use App\Workspace\Domain\Repository\UserRepositoryInterface;
use App\Workspace\Domain\Repository\WorkspaceTransactionInterface;
use App\Workspace\Domain\Service\WorkspacePlanner;
use App\Workspace\Infrastructure\Hrnest\HrnestClient;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class ImportHrnestPeopleHandler
{
    public function __construct(
        private readonly HrnestClient $hrnestClient,
        private readonly UserRepositoryInterface $userRepository,
        private readonly WorkspaceTransactionInterface $workspaceTransaction,
        private readonly OfficeLayoutRepositoryInterface $officeLayoutRepository,
        private readonly WorkspacePlanner $workspacePlanner,
        private readonly UserPasswordHasherInterface $passwordHasher,
        #[Autowire('%env(csv:HRNEST_DEFAULT_SCHEDULE)%')]
        private readonly array $defaultSchedule,
    ) {
    }

    public function handle(ImportHrnestPeopleCommand $command): ImportHrnestPeopleResult
    {
        $people = $this->hrnestClient->fetchPeople($command->peoplePath, $command->deskField);
        $deskMap = $this->workspacePlanner->buildDeskMap($this->officeLayoutRepository->findAllRooms());
        $warnings = [];
        $createdCount = 0;
        $updatedCount = 0;
        $deskAssignedCount = 0;
        $usersByEmail = [];

        foreach ($people as $person) {
            $emailKey = mb_strtolower($person->email);
            $user = $usersByEmail[$emailKey] ?? $this->userRepository->findByEmail($person->email);
            $assignedDeskId = $person->deskId;

            if ($assignedDeskId !== null && !isset($deskMap[$assignedDeskId])) {
                $warnings[] = sprintf(
                    'Pominieto przypisanie biurka "%s" dla %s <%s>, bo takie biurko nie istnieje lokalnie.',
                    $assignedDeskId,
                    $person->name,
                    $person->email,
                );
                $assignedDeskId = null;
            }

            if ($user === null) {
                $user = $this->createImportedUser($person->name, $person->email, $person->team, $assignedDeskId);

                if (!$command->dryRun) {
                    $this->userRepository->save($user);
                }

                $createdCount += 1;
            } else {
                $user->synchronizeFromHrnest($person->name, $person->team, $user->schedule(), $assignedDeskId);
                $updatedCount += 1;
            }

            $usersByEmail[$emailKey] = $user;

            if ($assignedDeskId !== null) {
                $deskAssignedCount += 1;
            }
        }

        if (!$command->dryRun) {
            $this->workspaceTransaction->flush();
        }

        return new ImportHrnestPeopleResult(
            count($people),
            $createdCount,
            $updatedCount,
            $deskAssignedCount,
            $warnings,
        );
    }

    private function createImportedUser(string $name, string $email, string $team, ?string $assignedDeskId): User
    {
        $temporaryUser = User::importFromHrnest(
            sprintf('u-%s', bin2hex(random_bytes(6))),
            $name,
            $email,
            $team,
            'temporary-hash',
            $assignedDeskId,
            $this->normalizedDefaultSchedule(),
        );

        $passwordHash = $this->passwordHasher->hashPassword($temporaryUser, bin2hex(random_bytes(24)));

        return User::importFromHrnest(
            $temporaryUser->id(),
            $temporaryUser->name(),
            $temporaryUser->email(),
            $temporaryUser->team(),
            $passwordHash,
            $assignedDeskId,
            $this->normalizedDefaultSchedule(),
        );
    }

    /**
     * @return list<string>
     */
    private function normalizedDefaultSchedule(): array
    {
        return array_values(array_filter(
            array_map(
                static fn (mixed $day): string => strtolower(trim((string) $day)),
                $this->defaultSchedule,
            ),
            static fn (string $day): bool => $day !== '',
        ));
    }
}
