<?php

declare(strict_types=1);

namespace App\Workspace\Domain\Repository;

use App\Workspace\Domain\Model\RecurringDeskReservation;

interface RecurringDeskReservationRepositoryInterface
{
    /**
     * @return list<RecurringDeskReservation>
     */
    public function findAll(): array;

    /**
     * @return list<RecurringDeskReservation>
     */
    public function findAllForUser(string $userId): array;

    public function add(RecurringDeskReservation $reservation): void;
}
