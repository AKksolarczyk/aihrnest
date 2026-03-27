<?php

declare(strict_types=1);

namespace App\Security;

use App\Workspace\Domain\Model\User;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class RegistrationConfirmationMailer
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly UrlGeneratorInterface $urlGenerator,
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

        $email = (new Email())
            ->from(new Address($this->fromAddress, 'Smart Desk'))
            ->to($user->email())
            ->subject('Potwierdz konto Smart Desk')
            ->text(
                "Czesc {$user->name()},\n\n".
                "Aby aktywowac konto Smart Desk, kliknij link:\n".
                "{$confirmationUrl}\n\n".
                "Po potwierdzeniu konta bedziesz mogl sie zalogowac."
            )
            ->html(
                '<p>Czesc '.htmlspecialchars($user->name(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').',</p>'.
                '<p>Aby aktywowac konto Smart Desk, kliknij link ponizej:</p>'.
                '<p><a href="'.htmlspecialchars($confirmationUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'">Potwierdz konto</a></p>'.
                '<p>Po potwierdzeniu konta bedziesz mogl sie zalogowac.</p>'
            );

        $this->mailer->send($email);
    }
}
