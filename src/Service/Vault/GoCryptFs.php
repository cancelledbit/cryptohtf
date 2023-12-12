<?php

namespace App\Service\Vault;

use App\Service\Vault\Contract\CryptFsInterface;
use Symfony\Component\Process\Process;

class GoCryptFs implements CryptFsInterface {
	public function __construct(private string $basePath) {
	}

	public function createStorage(string $pass, string $cryptDir): bool {
		$toInit = Process::fromShellCommandline("echo \"{$pass}\" | gocryptfs -init -q -- {$this->basePath}/{$cryptDir}");
	}

	public function decrypt(string $pass, string $cryptDir): string {
		// TODO: Implement decrypt() method.
	}

	public function close(string $cryptDir, string $mountDir): bool {
		// TODO: Implement close() method.
	}
}