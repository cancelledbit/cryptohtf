<?php

namespace App\Service\Vault;

use App\Entity\PersonalVault;
use App\Entity\User;
use App\Service\Vault\Contract\KeyInterface;
use App\Service\Vault\Contract\VaultInterface;
use App\Service\Vault\Exception\NoVaultException;
use App\Service\Vault\Exception\VaultExistsException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Process\Process;


class VaultFiles implements VaultInterface {
	private ?PersonalVault $vault;
	private VaultStatus $status;
	private ?User $user;

	public function __construct(
		Security $secutiry,
		private EntityManagerInterface $em,
		private int $durationOpen = 60
	) {
		$this->user = $this->em->getRepository(User::class)->findOneBy(['email' => $secutiry->getUser()?->getUserIdentifier()]);
		if (!$this->user) {
			throw new \UnexpectedValueException('No logged in user!');
		}
		$this->vault = $this->user->getPersonalVault();
		try {
			$this->status = $this->isMounted() ? VaultStatus::OPEN : VaultStatus::ENCRYPTED;
		} catch (NoVaultException) {
			$this->status = VaultStatus::NONE;
		}
	}

	public function getStatus(): VaultStatus {
		return $this->status;
	}

	public function isUninitialized(): bool {
		return $this->status === VaultStatus::NONE;
	}

	public function isOpen(): bool {
		return $this->status === VaultStatus::OPEN;
	}

	/**
	 * initialized vault and returns passphrase
	 */
	public function initVault(): string {
		if ($this->status !== VaultStatus::NONE) {
			throw new VaultExistsException("Vault for user {$this->user->getId()} already exists");
		}
		$rnd = random_bytes(255);
		$pass = sha1($rnd);
		$vault = $this->createEntity();
		$process = new Process();
		return $pass;
	}

	public function getMount(): string {
		return match ($this->status) {
			VaultStatus::OPEN => $this->getVault()->getMountPoint(),
			VaultStatus::ENCRYPTED, VaultStatus::NONE => throw new \InvalidArgumentException('Cant mount encrypted vault'),
		};
	}

	public function unlock(KeyInterface $key) {
		$this->status = VaultStatus::OPEN;
	}

	private function createEntity(): PersonalVault {
		$vault = new PersonalVault();
		$vault->setOwner($this->user);
		$vault->setCypherPoint(
			substr(
				md5($this->user->getEmail()), 0, 8,
			)
		);
		return $vault;
	}

	/**
	 * @throws NoVaultException
	 */
	private function getVault(): PersonalVault {
		if (!$this->vault) {
			throw new NoVaultException("No vault for user {$this->user->getId()}");
		}
		return $this->vault;
	}

	private function isMounted(): bool {
		return $this->isExpired() && $this->getVault()->getMountPoint() !== null;
	}

	private function isExpired(): bool {
		$mountTime = $this->getVault()->getLastMountTs();
		if (!$mountTime) {
			return true;
		}
		return $mountTime->getTimestamp() > time() - $this->durationOpen;
	}
}