<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class PricingController extends AbstractController
{
    #[Route('/pricing', name: 'app_pricing', methods: ['GET'])]
    public function __invoke(): Response
    {
        return $this->render('pricing/index.html.twig');
    }
}
