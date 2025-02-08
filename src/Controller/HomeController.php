<?php

declare(strict_types=1);

namespace App\Controller;

use App\VatServices\EuVatService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;


class HomeController extends AbstractController
{
    public function __construct(private readonly EuVatService $euVatService) {}

    #[Route('/', methods: ['GET', 'POST'])]
    public function index(Request $request): Response
    {
        $token = $request->getPayload()->get('token');

        if ($request->getMethod() === Request::METHOD_POST) {
            if (!$this->isCsrfTokenValid('vat-id', $token)) {
                $result =
                    [
                        'message' => 'CSRF token is not valid.'
                    ];
            } else {
                $result = $this->euVatService->getResult($request);
            }


            return $this->render('base.html.twig', $result);
        }

        return $this->render('base.html.twig');
    }
}
