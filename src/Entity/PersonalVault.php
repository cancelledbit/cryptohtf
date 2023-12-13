<?php

namespace App\Entity;

use App\Repository\PersonalVaultRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PersonalVaultRepository::class)]
class PersonalVault
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'personalVault', cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $owner = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $mountPoint = null;

    #[ORM\Column(length: 255)]
    private ?string $cypherPoint = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $lastMountTs = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(User $owner): static
    {
        $this->owner = $owner;

        return $this;
    }

    public function getMountPoint(): ?string
    {
        return $this->mountPoint;
    }

    public function setMountPoint(?string $mountPoint): static
    {
        $this->mountPoint = $mountPoint;

        return $this;
    }

    public function getCypherPoint(): ?string
    {
        return $this->cypherPoint;
    }

    public function setCypherPoint(string $cypherPoint): static
    {
        $this->cypherPoint = $cypherPoint;

        return $this;
    }

    public function getLastMountTs(): ?\DateTimeInterface
    {
        return $this->lastMountTs;
    }

    public function setLastMountTs(?\DateTimeInterface $lastMountTs): static
    {
        $this->lastMountTs = $lastMountTs;

        return $this;
    }
}
