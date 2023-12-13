<?php

namespace App\Controller;

use App\Service\Vault\Exception\VaultExistsException;
use App\Service\Vault\KeyPassphrase;
use App\Service\Vault\VaultHandler;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class VaultController extends AbstractController
{
    private const TIME_LEFT_THRESHOLD = 10;
	public function __construct(
		private VaultHandler $files,
	) {
	}

	/**
	 * @throws VaultExistsException
	 */
	#[Route('/vault', name: 'app_vault')]
    public function index(Request $req): Response
    {
		if (!$this->files->isInitialized()) {
			$pass = $this->files->initVault();
			return $this->render('vault/index.html.twig', [
				'pass' => $pass,
			]);
		}
		$errors = [];
		try {
			$passphrase = $req->get('passphrase');
			if ($passphrase) {
				$key = new KeyPassphrase($passphrase);
				$key->open($this->files);
			}
			if ($this->files->isOpen()) {
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
        $timeLeft = $this->files->getSecondsOpenLeft();
        $isOverdue = static fn(): bool => $timeLeft < self::TIME_LEFT_THRESHOLD;
        if ($isOverdue()) {
            $this->files->lock();
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
       if ($this->files->lock()) {
           return new Response('OK');
       }
       return new Response('FAIL', Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}
