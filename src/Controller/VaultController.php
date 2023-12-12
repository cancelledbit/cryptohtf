<?php

namespace App\Controller;

use App\Service\Vault\Exception\VaultExistsException;
use App\Service\Vault\KeyPassphrase;
use App\Service\Vault\VaultFiles;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class VaultController extends AbstractController
{
	public function __construct(
		private VaultFiles $files,
	) {
	}

	/**
	 * @throws VaultExistsException
	 */
	#[Route('/vault', name: 'app_vault')]
    public function index(Request $req): Response
    {
		if (false && $this->files->isUninitialized()) {
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
				return $this->redirect('/vault/manager?conf=manager');
			}
		} catch (\Throwable $e) {
			$errors = [$e->getMessage()];
		}

		return $this->render('vault/vault.html.twig', [
			'errors' => $errors,
		]);
    }
}
