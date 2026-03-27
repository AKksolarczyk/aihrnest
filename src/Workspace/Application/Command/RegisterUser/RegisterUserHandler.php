<?php

declare(strict_types=1);

namespace App\Workspace\Application\Command\RegisterUser;

use App\Workspace\Domain\Model\User;
use App\Workspace\Domain\Repository\OfficeLayoutRepositoryInterface;
use App\Workspace\Domain\Repository\UserRepositoryInterface;
use App\Workspace\Domain\Repository\WorkspaceTransactionInterface;
use App\Workspace\Domain\Service\WorkspacePlanner;
use InvalidArgumentException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class RegisterUserHandler
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly WorkspaceTransactionInterface $workspaceTransaction,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly OfficeLayoutRepositoryInterface $officeLayoutRepository,
        private readonly WorkspacePlanner $workspacePlanner,
    ) {
    }

    public function handle(RegisterUserCommand $command): User
    {
        if ($this->userRepository->findByEmail($command->email) !== null) {
            throw new InvalidArgumentException('Uzytkownik z takim adresem email juz istnieje.');
        }

        if (mb_strlen($command->plainPassword) < 8) {
            throw new InvalidArgumentException('Haslo musi miec co najmniej 8 znakow.');
        }

        $assignedDeskId = $command->assignedDeskId !== '' ? $command->assignedDeskId : null;
        $deskMap = $this->workspacePlanner->buildDeskMap($this->officeLayoutRepository->findAllRooms());

        if ($assignedDeskId !== null && !isset($deskMap[$assignedDeskId])) {
            throw new InvalidArgumentException('Wybrane biurko nie istnieje.');
        }

        $temporaryUser = User::register(
            sprintf('u-%s', bin2hex(random_bytes(6))),
            $command->name,
            $command->email,
            $command->team,
            'temporary-hash',
            $command->locale,
            $assignedDeskId,
            $command->schedule,
        );

        $hashedPassword = $this->passwordHasher->hashPassword($temporaryUser, $command->plainPassword);

        $user = User::register(
            $temporaryUser->id(),
            $temporaryUser->name(),
            $temporaryUser->email(),
            $temporaryUser->team(),
            $hashedPassword,
            $temporaryUser->locale(),
            $assignedDeskId,
            $command->schedule,
        );

        $this->userRepository->save($user);
        $this->workspaceTransaction->flush();

        return $user;
    }
}
