<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class VaultController extends AbstractController
{
    #[Route('/vault', name: 'app_vault')]
    public function index(): Response
    {
        return $this->render('vault/index.html.twig', [
            'controller_name' => 'VaultController',
        ]);
    }
}
