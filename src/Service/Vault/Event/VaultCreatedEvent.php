<?php

namespace App\Service\Vault\Event;

use App\Entity\PersonalVault;
use App\Service\Vault\Event\Contract\VaultEvent;

class VaultCreatedEvent extends VaultEvent {

    public function __construct(PersonalVault $vault, private string $pass) {
        parent::__construct($vault);
    }

    public function getPass(): string {
        return $this->pass;
    }

    static function getName(): string {
        return 'vault.created';
    }
}