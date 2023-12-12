<?php

namespace App\Service\Vault\Contract;

interface KeyInterface {
	public function getIdentity(): string;
	public function open(VaultInterface $vault): void;
}