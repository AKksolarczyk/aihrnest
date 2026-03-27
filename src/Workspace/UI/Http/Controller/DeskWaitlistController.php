<?php

declare(strict_types=1);

namespace App\Workspace\UI\Http\Controller;

use App\Workspace\Application\Command\ClaimDesk\ClaimDeskCommand;
use App\Workspace\Application\Command\ClaimDesk\ClaimDeskHandler;
use App\Workspace\Domain\Repository\DeskWaitlistRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

final class DeskWaitlistController extends AbstractController
{
    public function __construct(
        private readonly DeskWaitlistRepositoryInterface $deskWaitlistRepository,
    ) {
    }

    #[Route('/waitlist/claim/{token}', name: 'app_desk_waitlist_claim', methods: ['GET'])]
    public function claim(string $token, ClaimDeskHandler $handler): RedirectResponse
    {
        $entry = $this->deskWaitlistRepository->findByClaimToken($token);

        if ($entry === null || $entry->status() !== 'offered') {
            $this->addFlash('error', $this->trans('flash.waitlist.invalid_link'));

            return $this->redirectToRoute('app_login');
        }

        try {
            $handler->handle(new ClaimDeskCommand(
                $entry->userId(),
                $entry->deskId(),
                $entry->date(),
            ));

            $this->addFlash('success', $this->trans('flash.waitlist.claimed'));

            return $this->redirectToRoute('app_dashboard', ['date' => $entry->date()->format('Y-m-d')]);
        } catch (Throwable $exception) {
            $this->addFlash('error', $this->trans($exception->getMessage()));

            return $this->redirectToRoute('app_login');
        }
    }
}
