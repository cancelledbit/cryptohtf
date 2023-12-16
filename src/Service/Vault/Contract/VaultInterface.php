<?php

namespace App\Service\Vault\Contract;

use App\Service\Vault\Key\KeyInterface;

interface VaultInterface {
	public function unlock(KeyInterface $key): bool;
    public function lock(): bool;
    public function init();
    public function remove();
    public function getPlainFolder();
    public function getEncryptedFolder();
}