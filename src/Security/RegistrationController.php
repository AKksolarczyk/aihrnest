<?php

declare(strict_types=1);

namespace App\Security;

use App\Workspace\Application\Command\RegisterUser\RegisterUserCommand;
use App\Workspace\Application\Command\RegisterUser\RegisterUserHandler;
use App\Workspace\Domain\Repository\OfficeLayoutRepositoryInterface;
use App\Workspace\Domain\Service\WorkspacePlanner;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

final class RegistrationController extends AbstractController
{
    public function __construct(
        private readonly OfficeLayoutRepositoryInterface $officeLayoutRepository,
        private readonly WorkspacePlanner $workspacePlanner,
        private readonly RegistrationConfirmationMailer $registrationConfirmationMailer,
    ) {
    }

    #[Route('/register', name: 'app_register', methods: ['GET', 'POST'])]
    public function __invoke(Request $request, RegisterUserHandler $handler): Response|RedirectResponse
    {
        if ($request->isMethod('POST')) {
            try {
                $user = $handler->handle(new RegisterUserCommand(
                    $request->request->getString('name'),
                    $request->request->getString('email'),
                    $request->request->getString('team'),
                    $request->request->getString('password'),
                    $request->request->getString('assignedDeskId') ?: null,
                    $request->request->all('schedule'),
                ));

                try {
                    $this->registrationConfirmationMailer->send($user);
                    $this->addFlash('success', 'Konto zostalo utworzone. Wyslalismy email z linkiem aktywacyjnym.');
                } catch (Throwable) {
                    $this->addFlash('error', 'Konto zostalo utworzone, ale nie udalo sie wyslac maila aktywacyjnego.');
                }

                return $this->redirectToRoute('app_login');
            } catch (Throwable $exception) {
                $this->addFlash('error', $exception->getMessage());
            }
        }

        return $this->render('security/register.html.twig', [
            'deskOptions' => $this->workspacePlanner->buildDeskMap($this->officeLayoutRepository->findAllRooms()),
            'values' => [
                'name' => $request->request->getString('name'),
                'email' => $request->request->getString('email'),
                'team' => $request->request->getString('team'),
                'assignedDeskId' => $request->request->getString('assignedDeskId'),
                'schedule' => $request->request->all('schedule'),
            ],
        ]);
    }
}
