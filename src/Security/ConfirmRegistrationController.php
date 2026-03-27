<?php

declare(strict_types=1);

namespace App\Security;

use App\Workspace\Domain\Repository\UserRepositoryInterface;
use App\Workspace\Domain\Repository\WorkspaceTransactionInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Attribute\Route;

final class ConfirmRegistrationController extends AbstractController
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly WorkspaceTransactionInterface $workspaceTransaction,
    ) {
    }

    #[Route('/register/confirm/{token}', name: 'app_register_confirm', methods: ['GET'])]
    public function __invoke(string $token): RedirectResponse
    {
        $user = $this->userRepository->findByEmailConfirmationToken($token);

        if ($user === null) {
            $this->addFlash('error', 'Link aktywacyjny jest nieprawidlowy albo wygasl.');

            return $this->redirectToRoute('app_login');
        }

        $user->activate();
        $this->userRepository->save($user);
        $this->workspaceTransaction->flush();

        $this->addFlash('success', 'Konto zostalo aktywowane. Mozesz sie zalogowac.');

        return $this->redirectToRoute('app_login');
    }
}
