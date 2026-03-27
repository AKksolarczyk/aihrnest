<?php

declare(strict_types=1);

namespace App\Workspace\Application\Command\DeskWaitlistOffer;

use App\Workspace\Domain\Model\DeskWaitlistEntry;
use App\Workspace\Domain\Model\User;
use Twig\Environment;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class DeskWaitlistOfferMailer
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

    public function send(User $user, DeskWaitlistEntry $entry, string $deskLabel, string $roomName): void
    {
        $claimToken = $entry->claimToken();

        if ($claimToken === null) {
            return;
        }

        $claimUrl = $this->urlGenerator->generate('app_desk_waitlist_claim', [
            'token' => $claimToken,
        ], UrlGeneratorInterface::ABSOLUTE_URL);
        $locale = $user->locale();

        $email = (new Email())
            ->from(new Address($this->fromAddress, 'Smart Desk'))
            ->to($user->email())
            ->subject($this->translator->trans('mail.waitlist.subject', locale: $locale))
            ->text(
                $this->translator->trans('mail.waitlist.text', [
                    '%name%' => $user->name(),
                    '%desk%' => $deskLabel,
                    '%room%' => $roomName,
                    '%date%' => $entry->date()->format('Y-m-d'),
                    '%url%' => $claimUrl,
                ], locale: $locale)
            )
            ->html($this->twig->render('email/waitlist_offer.html.twig', [
                'locale' => $locale,
                'name' => $user->name(),
                'deskLabel' => $deskLabel,
                'roomName' => $roomName,
                'date' => $entry->date()->format('Y-m-d'),
                'claimUrl' => $claimUrl,
            ]));

        $this->mailer->send($email);
    }
}
