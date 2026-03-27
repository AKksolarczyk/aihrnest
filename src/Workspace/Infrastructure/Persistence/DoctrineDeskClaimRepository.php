<?php

declare(strict_types=1);

namespace App\Workspace\Infrastructure\Persistence;

use App\Workspace\Domain\Model\DeskClaim;
use App\Workspace\Domain\Repository\DeskClaimRepositoryInterface;
use DateTimeImmutable;
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

    public function findAllForUserInRange(string $userId, DateTimeImmutable $startDate, DateTimeImmutable $endDate): array
    {
        /** @var list<DeskClaim> $deskClaims */
        $deskClaims = $this->entityManager->createQueryBuilder()
            ->select('desk_claim')
            ->from(DeskClaim::class, 'desk_claim')
            ->where('desk_claim.userId = :userId')
            ->andWhere('desk_claim.date >= :startDate')
            ->andWhere('desk_claim.date <= :endDate')
            ->setParameter('userId', $userId)
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('desk_claim.date', 'ASC')
            ->getQuery()
            ->getResult();

        return $deskClaims;
    }

    public function findOneForUserAndDate(string $userId, DateTimeImmutable $date): ?DeskClaim
    {
        /** @var ?DeskClaim $deskClaim */
        $deskClaim = $this->entityManager->getRepository(DeskClaim::class)->findOneBy([
            'userId' => $userId,
            'date' => $date,
        ]);

        return $deskClaim;
    }

    public function add(DeskClaim $deskClaim): void
    {
        $this->entityManager->persist($deskClaim);
    }

    public function remove(DeskClaim $deskClaim): void
    {
        $this->entityManager->remove($deskClaim);
    }
}
