<?php

declare(strict_types=1);

namespace App\Infrastructure\Http;

use App\Workspace\Domain\Model\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class LocaleSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly Security $security,
    ) {
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $session = $request->hasSession() ? $request->getSession() : null;
        $locale = null;
        $user = $this->security->getUser();

        if ($user instanceof User) {
            $locale = $user->locale();
        } elseif ($session !== null && $session->has('_locale')) {
            $sessionLocale = $session->get('_locale');
            $locale = is_string($sessionLocale) ? $sessionLocale : null;
        }

        if ($locale === null) {
            return;
        }

        $request->setLocale($locale);

        if ($session !== null) {
            $session->set('_locale', $locale);
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 20],
        ];
    }
}
