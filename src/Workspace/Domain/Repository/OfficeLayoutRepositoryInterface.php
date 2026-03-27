<?php

declare(strict_types=1);

namespace App\Workspace\Domain\Repository;

use App\Workspace\Domain\Model\Room;

interface OfficeLayoutRepositoryInterface
{
    /**
     * @return list<Room>
     */
    public function findAllRooms(): array;
}
