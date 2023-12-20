<?php

namespace App\Service\Vault\Event\Contract;

use App\Entity\PersonalVault;
use Symfony\Contracts\EventDispatcher\Event;

abstract class VaultEvent extends Event
{
    abstract public static function getName(): string;

    public function __construct(protected PersonalVault $vault)
    {
    }

    public function getVault(): PersonalVault
    {
        return $this->vault;
    }
}
