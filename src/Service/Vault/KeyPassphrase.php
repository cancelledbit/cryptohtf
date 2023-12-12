<?php

namespace App\Service\Vault;

use App\Service\Vault\Contract\KeyInterface;
use App\Service\Vault\Contract\VaultInterface;

class KeyPassphrase implements KeyInterface {
	private const SALT = '43sdds4b';
	public function __construct(private readonly string $pass) {
	}

	public function getFingerprint(): string {
		return md5($this->pass . self::SALT);
	}

	public function open(VaultInterface $vault): bool {
		return $vault->unlock($this);
	}
}