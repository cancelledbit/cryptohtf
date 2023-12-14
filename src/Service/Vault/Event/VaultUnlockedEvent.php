<?php

namespace App\Service\Vault\Event;

use App\Service\Vault\Event\Contract\VaultEvent;

class VaultUnlockedEvent extends VaultEvent {

    static function getName(): string {
        return 'vault.unlocked';
    }
}