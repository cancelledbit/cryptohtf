<?php

namespace App\Service\Vault;

use App\Entity\User;
use App\Service\Vault\FS\CryptFsInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;


class VaultFactory {

	public function __construct(
		private EntityManagerInterface $em,
		private CryptFsInterface $cryptFs,
        private EventDispatcherInterface $dispatcher,
        private string $basePath,
		private int $keyLength,
		private int $durationOpen = 60,
	) {

	}

    public function getBySecurityContext(Security $security): Vault {
        $user = $this
            ->em
            ->getRepository(User::class)
            ->findOneBy(['email' => $security->getUser()?->getUserIdentifier()])
        ;
        if (!$user) {
            throw new \InvalidArgumentException('No user in security context');
        }
        return new Vault(
            $this->cryptFs,
            $user,
            $this->dispatcher,
            $this->em,
            $this->basePath,
            $this->keyLength,
            $this->durationOpen,
        );
    }

    public function getByUser(User $user): Vault {
        return new Vault(
            $this->cryptFs,
            $user,
            $this->dispatcher,
            $this->em,
            $this->basePath,
            $this->keyLength,
            $this->durationOpen,
        );
    }


}