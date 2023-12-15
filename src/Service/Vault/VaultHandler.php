<?php

namespace App\Service\Vault;

use App\Entity\PersonalVault;
use App\Entity\User;
use App\Service\Vault\Contract\CryptFsInterface;
use App\Service\Vault\Contract\KeyInterface;
use App\Service\Vault\Contract\VaultInterface;
use App\Service\Vault\Event\Subscriber\NotifierSubscriber;
use App\Service\Vault\Event\VaultCreatedEvent;
use App\Service\Vault\Event\VaultLockedEvent;
use App\Service\Vault\Event\VaultRemovedEvent;
use App\Service\Vault\Event\VaultUnlockedEvent;
use App\Service\Vault\Exception\NoVaultException;
use App\Service\Vault\Exception\VaultExistsException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Process\Process;


class VaultHandler implements VaultInterface {
	private ?PersonalVault $vault;
	private VaultStatus $status;
	private ?User $user;

	public function __construct(
		private Security $security,
		private EntityManagerInterface $em,
		private CryptFsInterface $cryptFs,
        private EventDispatcherInterface $dispatcher,
        private string $basePath,
		private int $keyLength,
		private int $durationOpen = 60
	) {
        $this->setUser(
            $this
                ->em
                ->getRepository(User::class)
                ->findOneBy(['email' => $this->security->getUser()?->getUserIdentifier()])
        );
	}

    public function setUser(?User $user): self {
        if (!$user) {
            $this->setStatus(VaultStatus::NON_EXISTENT);
            return $this;
        }
        $this->user = $user;
        $this->vault = $this->user->getPersonalVault();
        try {
            $this->setStatus($this->isMounted() ? VaultStatus::OPEN : VaultStatus::ENCRYPTED);
        } catch (NoVaultException) {
            $this->setStatus(VaultStatus::NONE);
        }
        return $this;
    }

    public function getSecondsOpenLeft(): int {
        $mountTime = $this->getVault()->getLastMountTs();
        if (!$mountTime) {
            return 0;
        }
        $timeOpen = $mountTime->getTimestamp();
        return $timeOpen + $this->durationOpen - time();
    }

    public function isExpired(): bool {
        $mountTime = $this->getVault()->getLastMountTs();
        if (!$mountTime) {
            return true;
        }
        return $mountTime->getTimestamp() < time() - $this->durationOpen;
    }

	private function getStatus(): VaultStatus {
		return $this->status;
	}

	private function setStatus(VaultStatus $status): void {
		$this->status = $status;
	}

	public function isInitialized(): bool {
		return match ($this->getStatus()) {
            VaultStatus::NON_EXISTENT, VaultStatus::NONE => false,
            default => true,
        };
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
		$pass = substr(sha1($rnd), 0, $this->keyLength);
		$key = new KeyPassphrase($pass);
		$vault = $this->createEntity();
		$res = $this->cryptFs->createStorage($key->getFingerprint(), $vault->getCypherPoint());
		if (!$res) {
			throw new \UnexpectedValueException('Unable to create storage, contact admin');
		}
		$this->em->persist($vault);
		$this->em->flush();
        $this->dispatcher->dispatch(new VaultCreatedEvent($vault, $pass), VaultCreatedEvent::getName());
		return $pass;
	}

	public function getMount(): string {
		return match ($this->getStatus()) {
			VaultStatus::OPEN => $this->basePath . '/' .$this->getVault()->getMountPoint(),
			VaultStatus::ENCRYPTED, VaultStatus::NONE, VaultStatus::NON_EXISTENT => throw new \InvalidArgumentException('Cant mount encrypted vault'),
		};
	}

	public function unlock(KeyInterface $key): bool{
		$mountPoint = $this->cryptFs->open($key->getFingerprint(), $this->getVault()->getCypherPoint());
		if ($mountPoint) {
			$this->setStatus(VaultStatus::OPEN);
			$this->getVault()->setMountPoint($mountPoint);
            $this->getVault()->setLastMountTs(new \DateTime());
			$this->em->persist($this->getVault());
			$this->em->flush();
            $this->dispatcher->dispatch(new VaultUnlockedEvent($this->getVault()), VaultUnlockedEvent::getName());
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
            $this->getVault()->setLastMountTs(null);
            $this->getVault()->setMountPoint(null);
            $this->em->persist($this->getVault());
            $this->em->flush();
            $this->dispatcher->dispatch(new VaultLockedEvent($this->vault), VaultLockedEvent::getName());
        }
        return $res;
	}

	/**
	 * @throws NoVaultException
	 */
	public function remove(): bool {
        if (!$this->security->isGranted('ROLE_ADMIN')) {
            throw new \UnexpectedValueException('Only admin can truncate folder');
        }
        if ($this->lock()) {
            $cypher = $this->getVault()->getCypherPoint();
            if (!$cypher) {
                return false;
            }
            if (!$this->cryptFs->remove($cypher)) {
                return false;
            }
            $this->em->remove($this->getVault());
            $this->em->flush();
            $this->dispatcher->dispatch(new VaultRemovedEvent($this->getVault()), VaultRemovedEvent::getName());
        }
        return false;
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
			throw new NoVaultException("No vault for user {$this->user?->getId()}");
		}
		return $this->vault;
	}

	private function isMounted(): bool {
		return (bool)$this->getVault()->getMountPoint();
	}
}