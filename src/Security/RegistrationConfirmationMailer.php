<?php

declare(strict_types=1);

namespace App\Security;

use App\Workspace\Domain\Model\User;
use Twig\Environment;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class RegistrationConfirmationMailer
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly TranslatorInterface $translator,
        private readonly Environment $twig,
        #[Autowire('%env(MAILER_FROM)%')]
        private readonly string $fromAddress,
    ) {
    }

    public function send(User $user): void
    {
        $token = $user->emailConfirmationToken();

        if ($token === null) {
            return;
        }

        $confirmationUrl = $this->urlGenerator->generate('app_register_confirm', [
            'token' => $token,
        ], UrlGeneratorInterface::ABSOLUTE_URL);
        $locale = $user->locale();

        $email = (new Email())
            ->from(new Address($this->fromAddress, 'Smart Desk'))
            ->to($user->email())
            ->subject($this->translator->trans('mail.registration.subject', locale: $locale))
            ->text(
                $this->translator->trans('mail.registration.text', [
                    '%name%' => $user->name(),
                    '%url%' => $confirmationUrl,
                ], locale: $locale)
            )
            ->html($this->twig->render('email/registration_confirmation.html.twig', [
                'locale' => $locale,
                'name' => $user->name(),
                'confirmationUrl' => $confirmationUrl,
            ]));

        $this->mailer->send($email);
    }
}
