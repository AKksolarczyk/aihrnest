<?php

declare(strict_types=1);

namespace App\Controller;

use App\Workspace\Domain\Model\User;
use App\Workspace\Domain\Repository\WorkspaceTransactionInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class LocaleController extends AbstractController
{
    #[Route('/locale/{locale}', name: 'app_locale_switch', methods: ['GET'])]
    public function __invoke(string $locale, Request $request, WorkspaceTransactionInterface $workspaceTransaction): RedirectResponse
    {
        if (!in_array($locale, User::SUPPORTED_LOCALES, true)) {
            $locale = User::DEFAULT_LOCALE;
        }

        $request->getSession()->set('_locale', $locale);

        $user = $this->getUser();

        if ($user instanceof User && $user->locale() !== $locale) {
            $user->changeLocale($locale);
            $workspaceTransaction->flush();
        }

        $referer = $request->headers->get('referer');

        if (is_string($referer) && $referer !== '') {
            return $this->redirect($referer);
        }

        return $this->redirectToRoute($user instanceof User ? 'app_dashboard' : 'app_login');
    }
}
