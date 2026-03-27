<?php

declare(strict_types=1);

namespace App\Workspace\Infrastructure\Persistence;

use App\Workspace\Domain\Model\DeskLabel;
use App\Workspace\Domain\Repository\DeskLabelRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineDeskLabelRepository implements DeskLabelRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function findByDeskId(string $deskId): ?DeskLabel
    {
        return $this->entityManager->find(DeskLabel::class, $deskId);
    }

    public function findAllIndexedByDeskId(): array
    {
        /** @var list<DeskLabel> $labels */
        $labels = $this->entityManager->getRepository(DeskLabel::class)->findAll();
        $indexed = [];

        foreach ($labels as $label) {
            $indexed[$label->deskId()] = $label;
        }

        return $indexed;
    }

    public function save(DeskLabel $deskLabel): void
    {
        $this->entityManager->persist($deskLabel);
    }
}
