<?php

namespace App\Service\Vault\Contract;

interface KeyInterface {
	public function getFingerprint(): string;
	public function open(VaultInterface $vault): bool;
}