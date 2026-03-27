<?php

declare(strict_types=1);

namespace App\Workspace\Application\Command\DeskWaitlistOffer;

use App\Workspace\Domain\Model\DeskWaitlistEntry;
use App\Workspace\Domain\Model\User;
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
            ->html(
                '<p>'.htmlspecialchars($this->translator->trans('mail.waitlist.greeting', [
                    '%name%' => $user->name(),
                ], locale: $locale), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'</p>'.
                '<p>'.htmlspecialchars($this->translator->trans('mail.waitlist.intro', [
                    '%desk%' => $deskLabel,
                    '%room%' => $roomName,
                    '%date%' => $entry->date()->format('Y-m-d'),
                ], locale: $locale), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'</p>'.
                '<p><a href="'.htmlspecialchars($claimUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'">'.
                htmlspecialchars($this->translator->trans('mail.waitlist.cta', locale: $locale), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').
                '</a></p>'.
                '<p>'.htmlspecialchars($this->translator->trans('mail.waitlist.outro', locale: $locale), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'</p>'
            );

        $this->mailer->send($email);
    }
}
