<?php

namespace App\Service\Vault;

use App\Entity\PersonalVault;
use App\Entity\User;
use App\Service\Vault\Contract\CryptFsInterface;
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
		Security                       $security,
		private EntityManagerInterface $em,
		private CryptFsInterface       $cryptFs,
        private string $basePath,
		private int                    $durationOpen = 60
	) {
		$this->user = $this->em->getRepository(User::class)->findOneBy(['email' => $security->getUser()?->getUserIdentifier()]);
		if (!$this->user) {
			throw new \UnexpectedValueException('No logged in user!');
		}
		$this->vault = $this->user->getPersonalVault();
		try {
			$this->setStatus($this->isMounted() ? VaultStatus::OPEN : VaultStatus::ENCRYPTED);
		} catch (NoVaultException) {
			$this->setStatus(VaultStatus::NONE);
		}
	}

	private function getStatus(): VaultStatus {
		return $this->status;
	}

	private function setStatus(VaultStatus $status): void {
		$this->status = $status;
	}

	public function isInitialized(): bool {
		return $this->getStatus() !== VaultStatus::NONE;
	}

	public function isOpen(): bool {
		return $this->getStatus() === VaultStatus::OPEN;
	}

	/**
	 * initialized vault and returns passphrase
	 */
	public function initVault(): string {
		if ($this->getStatus() !== VaultStatus::NONE) {
			throw new VaultExistsException("Vault for user {$this->user->getId()} already exists");
		}
		$rnd = random_bytes(255);
		$pass = sha1($rnd);
		$key = new KeyPassphrase($pass);
		$vault = $this->createEntity();
		$res = $this->cryptFs->createStorage($key->getFingerprint(), $vault->getCypherPoint());
		if (!$res) {
			throw new \UnexpectedValueException('Unable to create storage, contact admin');
		}
		$this->em->persist($vault);
		$this->em->flush();
		return $pass;
	}

	public function getMount(): string {
		return match ($this->getStatus()) {
			VaultStatus::OPEN => $this->basePath . '/' .$this->getVault()->getMountPoint(),
			VaultStatus::ENCRYPTED, VaultStatus::NONE => throw new \InvalidArgumentException('Cant mount encrypted vault'),
		};
	}

	public function unlock(KeyInterface $key): bool{
		$mountPoint = $this->cryptFs->decrypt($key->getFingerprint(), $this->getVault()->getCypherPoint());
		if ($mountPoint) {
			$this->setStatus(VaultStatus::OPEN);
			$this->getVault()->setMountPoint($mountPoint);
			$this->em->persist($this->getVault());
			$this->em->flush();
			return true;
		}
		return false;
	}

	public function lock(): bool {
		if (!$this->isMounted()) {
			return true;
		}
		$res = $this->cryptFs->close(
			$this->getVault()->getCypherPoint(),
			$this->getVault()->getMountPoint()
		);
        if ($res) {
            $this->setStatus(VaultStatus::ENCRYPTED);
        }
        return $res;
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
		return (bool)$this->getVault()->getMountPoint();
	}

	public function isExpired(): bool {
		$mountTime = $this->getVault()->getLastMountTs();
		if (!$mountTime) {
			return true;
		}
		return $mountTime->getTimestamp() > time() - $this->durationOpen;
	}
}