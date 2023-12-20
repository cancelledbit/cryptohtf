<?php

namespace App\Service\Vault\Event;

use App\Entity\PersonalVault;
use App\Service\Vault\Event\Contract\VaultEvent;

class VaultUpdatedEvent extends VaultEvent
{
    public function __construct(PersonalVault $vault, private string $pass, private bool $isNew)
    {
        parent::__construct($vault);
    }

    public function getPass(): string
    {
        return $this->pass;
    }

    public function isNew(): bool
    {
        return $this->isNew;
    }

    public static function getName(): string
    {
        return 'vault.created';
    }
}
