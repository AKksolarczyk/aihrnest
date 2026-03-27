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

final class DeskWaitlistOfferMailer
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly UrlGeneratorInterface $urlGenerator,
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

        $email = (new Email())
            ->from(new Address($this->fromAddress, 'Smart Desk'))
            ->to($user->email())
            ->subject('Biurko z waitlisty jest juz wolne')
            ->text(
                "Czesc {$user->name()},\n\n".
                "Biurko {$deskLabel} ({$roomName}) na dzien {$entry->date()->format('Y-m-d')} zostalo zwolnione.\n".
                "Kliknij link, aby je zajac:\n{$claimUrl}\n\n".
                "Link dziala tylko wtedy, gdy biurko nadal jest wolne."
            )
            ->html(
                '<p>Czesc '.htmlspecialchars($user->name(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').',</p>'.
                '<p>Biurko <strong>'.htmlspecialchars($deskLabel, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'</strong> '.
                '('.htmlspecialchars($roomName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').') na dzien '.
                htmlspecialchars($entry->date()->format('Y-m-d'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').' zostalo zwolnione.</p>'.
                '<p><a href="'.htmlspecialchars($claimUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'">Zajmij to biurko</a></p>'.
                '<p>Link zadziala tylko wtedy, gdy miejsce nadal bedzie wolne.</p>'
            );

        $this->mailer->send($email);
    }
}
