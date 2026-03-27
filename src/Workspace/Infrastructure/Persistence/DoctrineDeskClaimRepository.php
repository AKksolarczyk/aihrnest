<?php

declare(strict_types=1);

namespace App\Workspace\Infrastructure\Persistence;

use App\Workspace\Domain\Model\DeskClaim;
use App\Workspace\Domain\Repository\DeskClaimRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineDeskClaimRepository implements DeskClaimRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function findAll(): array
    {
        /** @var list<DeskClaim> $deskClaims */
        $deskClaims = $this->entityManager->getRepository(DeskClaim::class)->findBy([], ['date' => 'ASC']);

        return $deskClaims;
    }

    public function findAllForUser(string $userId): array
    {
        /** @var list<DeskClaim> $deskClaims */
        $deskClaims = $this->entityManager->getRepository(DeskClaim::class)->findBy(['userId' => $userId], ['date' => 'ASC']);

        return $deskClaims;
    }

    public function add(DeskClaim $deskClaim): void
    {
        $this->entityManager->persist($deskClaim);
    }
}
