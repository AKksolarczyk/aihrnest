<?php

declare(strict_types=1);

namespace App\Workspace\UI\Http\Controller;

use App\Workspace\Application\Command\ClaimDesk\ClaimDeskCommand;
use App\Workspace\Application\Command\ClaimDesk\ClaimDeskHandler;
use App\Workspace\Application\Command\RequestVacation\RequestVacationCommand;
use App\Workspace\Application\Command\RequestVacation\RequestVacationHandler;
use App\Workspace\Application\Query\GetDashboard\GetDashboardHandler;
use App\Workspace\Application\Query\GetDashboard\GetDashboardQuery;
use DateTimeImmutable;
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
        $activeUserId = $session->get('active_user_id', 'u1');
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
