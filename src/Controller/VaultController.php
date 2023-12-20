<?php

namespace App\Controller;

use App\Service\Vault\Exception\VaultExistsException;
use App\Service\Vault\Key\KeyPassphrase;
use App\Service\Vault\Vault;
use App\Service\Vault\VaultFactory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class VaultController extends AbstractController
{
    private Vault $vault;
    private const TIME_LEFT_THRESHOLD = 10;

    public function __construct(
        private VaultFactory $vaultFactory,
        private Security $security,
    ) {
        $this->vault = $this->vaultFactory->getBySecurityContext($this->security);
    }

    /**
     * @throws VaultExistsException
     */
    #[Route('/vault', name: 'app_vault')]
    public function index(Request $req): Response
    {
        if (!$this->vault->isInitialized()) {
            $pass = $this->vault->init();

            return $this->render('vault/index.html.twig', [
                'pass' => $pass,
            ]);
        }
        $errors = [];
        try {
            $passphrase = $req->get('passphrase');
            if ($passphrase) {
                $key = new KeyPassphrase($passphrase);
                $key->open($this->vault);
            }
            if ($this->vault->isOpen()) {
                return $this->redirect('/vault/viewer');
            } elseif ($passphrase) {
                throw new \InvalidArgumentException('Не удалось разблокировать хранилище. Неверный пароль или внутренняя ошибка');
            }
        } catch (\Throwable $e) {
            $errors = [$e->getMessage()];
        }

        return $this->render('vault/vault.html.twig', [
            'errors' => $errors,
        ]);
    }

    #[Route('/vault/viewer', name: 'app_vault_viewer')]
    public function fileViewer(Request $req): Response
    {
        $hasAccess = $this->isGranted('ROLE_USER');
        $timeLeft = $this->vault->getSecondsOpenLeft();
        $isOverdue = static fn (): bool => $timeLeft < self::TIME_LEFT_THRESHOLD;
        if ($isOverdue()) {
            $this->vault->lock();
        }
        if (!$hasAccess || $isOverdue()) {
            return $this->redirectToRoute('app_vault');
        }

        return $this->render('vault/vault_viewer.html.twig', [
            'time_left' => $timeLeft,
        ]);
    }

    #[Route('/vault/lock', name: 'app_vault_lock', methods: ['POST'])]
    public function lock(Request $req): Response
    {
        if ($this->vault->lock()) {
            return new Response('OK');
        }

        return new Response('FAIL', Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}
