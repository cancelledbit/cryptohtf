<?php

namespace App\Service\Vault\Key;

use App\Service\Vault\Contract\VaultInterface;

interface KeyInterface {
	public function getFingerprint(): string;
	public function open(VaultInterface $vault): bool;
}