<?php

declare(strict_types=1);

namespace App\Workspace\Application\Command\ReportIssue;

use App\Workspace\Domain\Model\IssueReport;
use App\Workspace\Domain\Model\User;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class IssueReportNotificationMailer
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly TranslatorInterface $translator,
        #[Autowire('%env(MAILER_FROM)%')]
        private readonly string $fromAddress,
    ) {
    }

    public function send(User $recipient, User $reporter, IssueReport $issueReport, string $resourceLabel): void
    {
        $locale = $recipient->locale();
        $dashboardUrl = $this->urlGenerator->generate('app_dashboard', [], UrlGeneratorInterface::ABSOLUTE_URL);

        $email = (new Email())
            ->from(new Address($this->fromAddress, 'Smart Desk'))
            ->to($recipient->email())
            ->subject($this->translator->trans('mail.issue.subject', locale: $locale))
            ->text(
                $this->translator->trans('mail.issue.text', [
                    '%admin%' => $recipient->name(),
                    '%reporter%' => $reporter->name(),
                    '%category%' => $this->translator->trans(sprintf('dashboard.issue_category.%s', $issueReport->category()), locale: $locale),
                    '%resource%' => $resourceLabel,
                    '%description%' => $issueReport->description(),
                    '%url%' => $dashboardUrl,
                ], locale: $locale)
            )
            ->html(
                '<p>'.htmlspecialchars($this->translator->trans('mail.issue.greeting', [
                    '%admin%' => $recipient->name(),
                ], locale: $locale), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'</p>'.
                '<p>'.htmlspecialchars($this->translator->trans('mail.issue.intro', [
                    '%reporter%' => $reporter->name(),
                    '%category%' => $this->translator->trans(sprintf('dashboard.issue_category.%s', $issueReport->category()), locale: $locale),
                    '%resource%' => $resourceLabel,
                ], locale: $locale), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'</p>'.
                '<p><strong>'.htmlspecialchars($this->translator->trans('mail.issue.description_label', locale: $locale), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').':</strong> '.
                htmlspecialchars($issueReport->description(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'</p>'.
                '<p><a href="'.htmlspecialchars($dashboardUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'">'.
                htmlspecialchars($this->translator->trans('mail.issue.cta', locale: $locale), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').
                '</a></p>'
            );

        $this->mailer->send($email);
    }
}
