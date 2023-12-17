<?php

namespace App\Service\Vault;

use App\Entity\PersonalVault;
use App\Entity\User;
use App\Service\Vault\Contract\VaultInterface;
use App\Service\Vault\Event\VaultCreatedEvent;
use App\Service\Vault\Event\VaultLockedEvent;
use App\Service\Vault\Event\VaultRemovedEvent;
use App\Service\Vault\Event\VaultUnlockedEvent;
use App\Service\Vault\Exception\NoVaultException;
use App\Service\Vault\Exception\VaultExistsException;
use App\Service\Vault\FS\CryptFsInterface;
use App\Service\Vault\Key\KeyInterface;
use App\Service\Vault\Key\KeyPassphrase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final class Vault implements VaultInterface {
    private ?PersonalVault $personalVault;
    private VaultStatus $status;
    public function __construct(
        private CryptFsInterface $cryptFs,
        private User $user,
        private EventDispatcherInterface $dispatcher,
        private EntityManagerInterface $em,
        private string $basePath,
        private int $keyLength,
        private int $durationOpen = 60
    ) {
        $this->personalVault = $this->user->getPersonalVault();
        $this->updateStatus();
    }

    public function getSecondsOpenLeft(): int {
        $mountTime = $this->getPersonalVault()->getLastMountTs();
        if (!$mountTime) {
            return 0;
        }
        $timeOpen = $mountTime->getTimestamp();
        return $timeOpen + $this->durationOpen - time();
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

    public function isExpired(): bool {
        $mountTime = $this->getPersonalVault()->getLastMountTs();
        if (!$mountTime) {
            return true;
        }
        return $mountTime->getTimestamp() < time() - $this->durationOpen;
    }

    private function updateStatus(): void {
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

    public function unlock(KeyInterface $key): bool {
        $mountPoint = $this->cryptFs->open($key->getFingerprint(), $this->getPersonalVault()->getCypherPoint());
        if ($mountPoint) {
            $this->setStatus(VaultStatus::OPEN);
            $this->getPersonalVault()->setMountPoint($mountPoint);
            $this->getPersonalVault()->setLastMountTs(new \DateTime());
            $this->em->persist($this->getPersonalVault());
            $this->em->flush();
            $this->dispatcher->dispatch(new VaultUnlockedEvent($this->getPersonalVault()), VaultUnlockedEvent::getName());
            return true;
        }
        return false;
    }

    public function lock(): bool {
        if (!$this->isMounted()) {
            return true;
        }
        $res = $this->cryptFs->close(
            $this->getPersonalVault()->getCypherPoint(),
            $this->getPersonalVault()->getMountPoint()
        );
        if ($res) {
            $this->setStatus(VaultStatus::ENCRYPTED);
            $this->getPersonalVault()->setLastMountTs(null);
            $this->getPersonalVault()->setMountPoint(null);
            $this->em->persist($this->getPersonalVault());
            $this->em->flush();
            $this->dispatcher->dispatch(new VaultLockedEvent($this->personalVault), VaultLockedEvent::getName());
        }
        return $res;
    }

    public function init(): string {
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
        $this->updateStatus();
        return $pass;
    }

    public function remove(): bool {
        if ($this->lock()) {
            $cypher = $this->getPersonalVault()->getCypherPoint();
            if (!$cypher) {
                return false;
            }
            if (!$this->cryptFs->remove($cypher)) {
                return false;
            }
            $this->em->remove($this->getPersonalVault());
            $this->em->flush();
            $this->dispatcher->dispatch(new VaultRemovedEvent($this->getPersonalVault()), VaultRemovedEvent::getName());
        }
        return false;
    }

    public function getPlainFolder(): string {
        return match ($this->getStatus()) {
            VaultStatus::OPEN => $this->basePath . '/' .$this->getPersonalVault()->getMountPoint(),
            VaultStatus::ENCRYPTED, VaultStatus::NONE, VaultStatus::NON_EXISTENT => throw new \InvalidArgumentException('Cant mount encrypted vault'),
        };
    }

    public function getEncryptedFolder(): string {
       return $this->getPersonalVault()->getCypherPoint();
    }

    /**
     * @throws NoVaultException
     */
    private function getPersonalVault(): PersonalVault {
        if (!$this->personalVault) {
            throw new NoVaultException("No vault for user {$this->user?->getId()}");
        }
        return $this->personalVault;
    }

    private function isMounted(): bool {
        return (bool)$this->getPersonalVault()->getMountPoint();
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
}