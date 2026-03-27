<?php

declare(strict_types=1);

namespace App\Workspace\Domain\Model;

enum UserRole: string
{
    case User = 'user';
    case Admin = 'admin';
}
