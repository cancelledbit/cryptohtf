<?php

namespace App\Service\Vault\Contract;

interface CryptFsInterface {
	public function createStorage(string $pass, string $cryptDir): bool;
	public function decrypt(string $pass, string $cryptDir): string;
	public function close(string $cryptDir, string $mountDir): bool;
}