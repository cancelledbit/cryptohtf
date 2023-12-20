<?php

namespace App\Service\Vault\Event;

use App\Service\Vault\Event\Contract\VaultEvent;

class VaultLockedEvent extends VaultEvent
{
    public static function getName(): string
    {
        return 'vault.locked';
    }
}
