<?php

declare(strict_types=1);

namespace App\Workspace\Application\Command\ReportIssue;

use App\Workspace\Domain\Model\IssueReport;
use App\Workspace\Domain\Model\User;
use Twig\Environment;
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
        private readonly Environment $twig,
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
            ->html($this->twig->render('email/issue_report_notification.html.twig', [
                'locale' => $locale,
                'adminName' => $recipient->name(),
                'reporterName' => $reporter->name(),
                'categoryLabel' => $this->translator->trans(sprintf('dashboard.issue_category.%s', $issueReport->category()), locale: $locale),
                'resourceLabel' => $resourceLabel,
                'description' => $issueReport->description(),
                'dashboardUrl' => $dashboardUrl,
            ]));

        $this->mailer->send($email);
    }
}
