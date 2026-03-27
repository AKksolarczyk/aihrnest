<?php

declare(strict_types=1);

namespace App\Security;

use App\Workspace\Domain\Model\User;
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
            ->html(
                '<p>'.htmlspecialchars($this->translator->trans('mail.registration.greeting', [
                    '%name%' => $user->name(),
                ], locale: $locale), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'</p>'.
                '<p>'.htmlspecialchars($this->translator->trans('mail.registration.intro', locale: $locale), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'</p>'.
                '<p><a href="'.htmlspecialchars($confirmationUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'">'.
                htmlspecialchars($this->translator->trans('mail.registration.cta', locale: $locale), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').
                '</a></p>'.
                '<p>'.htmlspecialchars($this->translator->trans('mail.registration.outro', locale: $locale), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'</p>'
            );

        $this->mailer->send($email);
    }
}
