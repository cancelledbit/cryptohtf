<?php

namespace App\Service\Vault\Contract;

interface VaultInterface {
	public function unlock(KeyInterface $key): bool;
}