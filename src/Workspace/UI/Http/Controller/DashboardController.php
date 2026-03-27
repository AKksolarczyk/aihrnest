<?php

declare(strict_types=1);

namespace App\Workspace\UI\Http\Controller;

use App\Workspace\Application\Command\ClaimDesk\ClaimDeskCommand;
use App\Workspace\Application\Command\ClaimDesk\ClaimDeskHandler;
use App\Workspace\Application\Command\CreateRecurringDeskReservation\CreateRecurringDeskReservationCommand;
use App\Workspace\Application\Command\CreateRecurringDeskReservation\CreateRecurringDeskReservationHandler;
use App\Workspace\Application\Command\JoinDeskWaitlist\JoinDeskWaitlistCommand;
use App\Workspace\Application\Command\JoinDeskWaitlist\JoinDeskWaitlistHandler;
use App\Workspace\Application\Command\RequestVacation\RequestVacationCommand;
use App\Workspace\Application\Command\RequestVacation\RequestVacationHandler;
use App\Workspace\Application\Command\ReportIssue\ReportIssueCommand;
use App\Workspace\Application\Command\ReportIssue\ReportIssueHandler;
use App\Workspace\Application\Command\ReleaseDeskClaim\ReleaseDeskClaimCommand;
use App\Workspace\Application\Command\ReleaseDeskClaim\ReleaseDeskClaimHandler;
use App\Workspace\Application\Query\GetDashboard\GetDashboardHandler;
use App\Workspace\Application\Query\GetDashboard\GetDashboardQuery;
use App\Workspace\Domain\Model\DeskLabel;
use App\Workspace\Domain\Repository\DeskLabelRepositoryInterface;
use App\Workspace\Domain\Repository\OfficeLayoutRepositoryInterface;
use App\Workspace\Domain\Repository\WorkspaceTransactionInterface;
use App\Workspace\Domain\Service\WorkspacePlanner;
use DateTimeImmutable;
use InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

final class DashboardController extends AbstractController
{
    #[Route('/', name: 'app_dashboard', methods: ['GET'])]
    public function index(Request $request, SessionInterface $session, GetDashboardHandler $handler): Response
    {
        $selectedDate = $this->resolveDate($request->query->getString('date'));
        $securityUser = $this->getUser();
        $defaultUserId = method_exists($securityUser, 'id') ? $securityUser->id() : 'u1';
        $activeUserId = $session->get('active_user_id', $defaultUserId);
        $dashboardView = $handler->handle(new GetDashboardQuery($selectedDate, $activeUserId));

        return $this->render('dashboard/index.html.twig', $dashboardView->toArray());
    }

    #[Route('/active-user', name: 'app_active_user', methods: ['POST'])]
    public function changeActiveUser(Request $request, SessionInterface $session): RedirectResponse
    {
        $session->set('active_user_id', $request->request->getString('userId', 'u1'));

        return $this->redirectToRoute('app_dashboard', [
            'date' => $request->request->getString('date', date('Y-m-d')),
        ]);
    }

    #[Route('/vacations', name: 'app_vacation_request', methods: ['POST'])]
    public function requestVacation(
        Request $request,
        SessionInterface $session,
        RequestVacationHandler $handler,
    ): RedirectResponse {
        $date = $request->request->getString('date', date('Y-m-d'));

        try {
            $handler->handle(new RequestVacationCommand(
                $request->request->getString('userId'),
                new DateTimeImmutable($request->request->getString('startDate')),
                new DateTimeImmutable($request->request->getString('endDate')),
            ));

            $session->getFlashBag()->add('success', 'Urlop zostal zapisany, a przypisane biurko zostanie zwolnione.');
        } catch (Throwable $exception) {
            $session->getFlashBag()->add('error', $exception->getMessage());
        }

        return $this->redirectToRoute('app_dashboard', ['date' => $date]);
    }

    #[Route('/claims', name: 'app_desk_claim', methods: ['POST'])]
    public function claimDesk(
        Request $request,
        SessionInterface $session,
        ClaimDeskHandler $handler,
    ): RedirectResponse {
        $date = $request->request->getString('date', date('Y-m-d'));

        try {
            $handler->handle(new ClaimDeskCommand(
                $request->request->getString('userId'),
                $request->request->getString('deskId'),
                new DateTimeImmutable($date),
            ));

            $session->getFlashBag()->add('success', 'Wolne biurko zostalo zajete.');
        } catch (Throwable $exception) {
            $session->getFlashBag()->add('error', $exception->getMessage());
        }

        return $this->redirectToRoute('app_dashboard', ['date' => $date]);
    }

    #[Route('/claims/release', name: 'app_desk_claim_release', methods: ['POST'])]
    public function releaseDeskClaim(
        Request $request,
        SessionInterface $session,
        ReleaseDeskClaimHandler $handler,
    ): RedirectResponse {
        $date = $request->request->getString('date', date('Y-m-d'));

        try {
            $handler->handle(new ReleaseDeskClaimCommand(
                $request->request->getString('userId'),
                new DateTimeImmutable($date),
            ));

            $session->getFlashBag()->add('success', 'Biurko zostalo zwolnione.');
        } catch (Throwable $exception) {
            $session->getFlashBag()->add('error', $exception->getMessage());
        }

        return $this->redirectToRoute('app_dashboard', ['date' => $date]);
    }

    #[Route('/waitlist', name: 'app_desk_waitlist_join', methods: ['POST'])]
    public function joinDeskWaitlist(
        Request $request,
        SessionInterface $session,
        JoinDeskWaitlistHandler $handler,
    ): RedirectResponse {
        $date = $request->request->getString('date', date('Y-m-d'));

        try {
            $handler->handle(new JoinDeskWaitlistCommand(
                $request->request->getString('userId'),
                $request->request->getString('deskId'),
                new DateTimeImmutable($date),
            ));

            $session->getFlashBag()->add('success', 'Uzytkownik zostal dodany do waitlisty dla wybranego biurka.');
        } catch (Throwable $exception) {
            $session->getFlashBag()->add('error', $exception->getMessage());
        }

        return $this->redirectToRoute('app_dashboard', ['date' => $date]);
    }

    #[Route('/recurring-claims', name: 'app_recurring_desk_claim_create', methods: ['POST'])]
    public function createRecurringDeskClaim(
        Request $request,
        SessionInterface $session,
        CreateRecurringDeskReservationHandler $handler,
    ): RedirectResponse {
        $date = $request->request->getString('date', date('Y-m-d'));

        try {
            $result = $handler->handle(new CreateRecurringDeskReservationCommand(
                $request->request->getString('userId'),
                $request->request->getString('deskId'),
                new DateTimeImmutable($request->request->getString('startDate')),
                new DateTimeImmutable($request->request->getString('endDate')),
                $request->request->all('weekdays'),
            ));

            $session->getFlashBag()->add(
                'success',
                sprintf('Zapisano rezerwacje cykliczna. Utworzono %d zajec, pominieto %d konfliktow.', $result->createdClaims, $result->skippedClaims),
            );
        } catch (Throwable $exception) {
            $session->getFlashBag()->add('error', $exception->getMessage());
        }

        return $this->redirectToRoute('app_dashboard', ['date' => $date]);
    }

    #[Route('/issues', name: 'app_issue_report_create', methods: ['POST'])]
    public function reportIssue(
        Request $request,
        SessionInterface $session,
        ReportIssueHandler $handler,
    ): RedirectResponse {
        $date = $request->request->getString('date', date('Y-m-d'));

        try {
            $handler->handle(new ReportIssueCommand(
                $request->request->getString('userId'),
                $request->request->getString('deskId') ?: null,
                $request->request->getString('roomId') ?: null,
                $request->request->getString('category'),
                $request->request->getString('description'),
            ));

            $session->getFlashBag()->add('success', 'Zgloszenie problemu zostalo zapisane.');
        } catch (Throwable $exception) {
            $session->getFlashBag()->add('error', $exception->getMessage());
        }

        return $this->redirectToRoute('app_dashboard', ['date' => $date]);
    }

    #[Route('/admin/desks/labels', name: 'app_admin_desk_label_update', methods: ['POST'])]
    public function updateDeskLabels(
        Request $request,
        SessionInterface $session,
        OfficeLayoutRepositoryInterface $officeLayoutRepository,
        DeskLabelRepositoryInterface $deskLabelRepository,
        WorkspacePlanner $workspacePlanner,
        WorkspaceTransactionInterface $transaction,
    ): RedirectResponse {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $date = $request->request->getString('date', date('Y-m-d'));
        $activeUserId = $request->request->getString('activeUserId');
        $activeTab = $request->request->getString('tab', 'admin-desks');

        try {
            /** @var array<string, mixed> $labels */
            $labels = $request->request->all('labels');
            $deskMap = $workspacePlanner->buildDeskMap($officeLayoutRepository->findAllRooms());
            $updatedDesks = 0;

            foreach ($labels as $deskId => $label) {
                if (!is_string($deskId) || !is_scalar($label)) {
                    continue;
                }

                if (!isset($deskMap[$deskId])) {
                    throw new InvalidArgumentException('Wybrane biurko nie istnieje.');
                }

                $deskLabel = $deskLabelRepository->findByDeskId($deskId) ?? new DeskLabel($deskId, (string) $label);
                $deskLabel->rename((string) $label);
                $deskLabelRepository->save($deskLabel);
                $updatedDesks += 1;
            }

            if ($updatedDesks === 0) {
                throw new InvalidArgumentException('Nie przeslano zadnych nazw biurek do zapisania.');
            }

            $transaction->flush();

            $session->getFlashBag()->add('success', sprintf('Zaktualizowano nazwy %d biurek.', $updatedDesks));
        } catch (Throwable $exception) {
            $session->getFlashBag()->add('error', $exception->getMessage());
        }

        if ($activeUserId !== '') {
            $session->set('active_user_id', $activeUserId);
        }

        return $this->redirectToRoute('app_dashboard', [
            'date' => $date,
            'tab' => $activeTab,
        ]);
    }

    private function resolveDate(string $rawDate): DateTimeImmutable
    {
        if ($rawDate === '') {
            return new DateTimeImmutable('today');
        }

        try {
            return new DateTimeImmutable($rawDate);
        } catch (\Exception) {
            return new DateTimeImmutable('today');
        }
    }
}
