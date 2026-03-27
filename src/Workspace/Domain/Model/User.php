<?php

declare(strict_types=1);

namespace App\Workspace\Domain\Model;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity]
#[ORM\Table(name: 'workspace_users')]
#[ORM\UniqueConstraint(name: 'uniq_workspace_users_email', columns: ['email'])]
final class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    /**
     * @param list<string> $roles
     * @param list<string> $schedule
     */
    public function __construct(
        #[ORM\Id]
        #[ORM\Column(type: 'string', length: 32)]
        private string $id,
        #[ORM\Column(type: 'string', length: 255)]
        private string $name,
        #[ORM\Column(type: 'string', length: 180)]
        private string $email,
        #[ORM\Column(type: 'string', length: 255)]
        private string $team,
        #[ORM\Column(name: 'password_hash', type: 'string', length: 255)]
        private string $passwordHash,
        #[ORM\Column(type: 'json')]
        private array $roles,
        #[ORM\Column(type: 'boolean')]
        private bool $isActive,
        #[ORM\Column(type: 'string', length: 64, nullable: true)]
        private ?string $emailConfirmationToken,
        #[ORM\Column(type: 'string', length: 32, nullable: true)]
        private ?string $assignedDeskId,
        /** @var list<string> */
        #[ORM\Column(type: 'json')]
        private array $schedule,
        #[ORM\Column(type: 'integer')]
        private int $vacationDaysTotal,
        #[ORM\Column(type: 'integer')]
        private int $vacationDaysRemaining,
    ) {
        if ($this->id === '') {
            throw new InvalidArgumentException('User id cannot be empty.');
        }

        $this->roles = self::normalizeRoles($this->roles);

        if ($this->email === '') {
            throw new InvalidArgumentException('User email cannot be empty.');
        }

        if ($this->passwordHash === '') {
            throw new InvalidArgumentException('Password hash cannot be empty.');
        }

        if ($this->assignedDeskId === '') {
            $this->assignedDeskId = null;
        }

        if ($this->vacationDaysTotal < 0 || $this->vacationDaysRemaining < 0) {
            throw new InvalidArgumentException('Vacation days cannot be negative.');
        }
    }

    /**
     * @param list<string> $schedule
     */
    public static function register(
        string $id,
        string $name,
        string $email,
        string $team,
        string $passwordHash,
        ?string $assignedDeskId = null,
        array $schedule = [],
        int $vacationDaysTotal = 26,
        array $roles = ['ROLE_USER'],
    ): self {
        return new self(
            $id,
            trim($name),
            mb_strtolower(trim($email)),
            trim($team),
            $passwordHash,
            self::normalizeRoles($roles),
            false,
            bin2hex(random_bytes(32)),
            $assignedDeskId,
            array_values($schedule),
            $vacationDaysTotal,
            $vacationDaysTotal,
        );
    }

    /**
     * @param list<string> $schedule
     * @param list<string> $roles
     */
    public static function importFromHrnest(
        string $id,
        string $name,
        string $email,
        string $team,
        string $passwordHash,
        ?string $assignedDeskId = null,
        array $schedule = [],
        int $vacationDaysTotal = 26,
        array $roles = ['ROLE_USER'],
    ): self {
        return new self(
            $id,
            trim($name),
            mb_strtolower(trim($email)),
            trim($team),
            $passwordHash,
            self::normalizeRoles($roles),
            true,
            null,
            $assignedDeskId,
            array_values($schedule),
            $vacationDaysTotal,
            $vacationDaysTotal,
        );
    }

    public function id(): string
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function email(): string
    {
        return $this->email;
    }

    public function team(): string
    {
        return $this->team;
    }

    public function assignedDeskId(): ?string
    {
        return $this->assignedDeskId;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function emailConfirmationToken(): ?string
    {
        return $this->emailConfirmationToken;
    }

    /**
     * @return list<string>
     */
    public function schedule(): array
    {
        return $this->schedule;
    }

    public function vacationDaysTotal(): int
    {
        return $this->vacationDaysTotal;
    }

    public function vacationDaysRemaining(): int
    {
        return $this->vacationDaysRemaining;
    }

    public function hasAssignedDesk(): bool
    {
        return $this->assignedDeskId !== null;
    }

    public function activate(): void
    {
        $this->isActive = true;
        $this->emailConfirmationToken = null;
    }

    /**
     * @param list<string> $schedule
     */
    public function synchronizeFromHrnest(
        string $name,
        string $team,
        array $schedule,
        ?string $assignedDeskId,
    ): void {
        $normalizedName = trim($name);
        $normalizedTeam = trim($team);

        if ($normalizedName === '') {
            throw new InvalidArgumentException('User name cannot be empty.');
        }

        if ($normalizedTeam === '') {
            throw new InvalidArgumentException('User team cannot be empty.');
        }

        $this->name = $normalizedName;
        $this->team = $normalizedTeam;
        $this->schedule = array_values($schedule);
        $this->assignedDeskId = $assignedDeskId !== '' ? $assignedDeskId : null;
        $this->isActive = true;
        $this->emailConfirmationToken = null;
    }

    public function isScheduledOn(DateTimeImmutable $date): bool
    {
        return in_array(strtolower($date->format('l')), $this->schedule, true);
    }

    public function consumeVacationDays(int $requestedDays): void
    {
        if ($requestedDays < 1) {
            throw new InvalidArgumentException('Requested vacation days must be greater than zero.');
        }

        if ($requestedDays > $this->vacationDaysRemaining) {
            throw new InvalidArgumentException(sprintf(
                'Brakuje dni urlopu. Wniosek wymaga %d dni roboczych, a pozostalo %d.',
                $requestedDays,
                $this->vacationDaysRemaining,
            ));
        }

        $this->vacationDaysRemaining -= $requestedDays;
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    /**
     * @return list<string>
     */
    public function getRoles(): array
    {
        $roles = self::normalizeRoles($this->roles);
        $roles[] = 'ROLE_USER';

        return array_values(array_unique($roles));
    }

    /**
     * @param list<string> $roles
     * @return list<string>
     */
    private static function normalizeRoles(array $roles): array
    {
        $normalized = array_map(
            static fn (string $role): string => strtoupper(trim($role)),
            $roles,
        );

        $normalized = array_values(array_filter(
            $normalized,
            static fn (string $role): bool => $role !== '',
        ));

        if ($normalized === []) {
            $normalized = ['ROLE_USER'];
        }

        return array_values(array_unique($normalized));
    }

    public function getPassword(): string
    {
        return $this->passwordHash;
    }

    public function eraseCredentials(): void
    {
    }
}
