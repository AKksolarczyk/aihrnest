<?php

declare(strict_types=1);

namespace App\Workspace\Domain\Model;

final class WorkspaceState
{
    /**
     * @param list<User> $users
     * @param list<Vacation> $vacations
     * @param list<DeskClaim> $deskClaims
     */
    public function __construct(
        private array $users,
        private array $vacations,
        private array $deskClaims,
    ) {
    }

    /**
     * @return list<User>
     */
    public function users(): array
    {
        return $this->users;
    }

    /**
     * @return list<Vacation>
     */
    public function vacations(): array
    {
        return $this->vacations;
    }

    /**
     * @return list<DeskClaim>
     */
    public function deskClaims(): array
    {
        return $this->deskClaims;
    }

    public function findUser(string $userId): ?User
    {
        foreach ($this->users as $user) {
            if ($user->id() === $userId) {
                return $user;
            }
        }

        return null;
    }

    public function addVacation(Vacation $vacation): void
    {
        $this->vacations[] = $vacation;
    }

    public function addDeskClaim(DeskClaim $deskClaim): void
    {
        $this->deskClaims[] = $deskClaim;
    }
}
