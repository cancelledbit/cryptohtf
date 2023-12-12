<?php

namespace App\Service\Vault;

use App\Service\Vault\Contract\CryptFsInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Process\Process;
use function Symfony\Component\Translation\t;

class GoCryptFs implements CryptFsInterface {
	public function __construct(private string $basePath, private LoggerInterface $logger) {
	}

	public function createStorage(string $pass, string $cryptDir): bool {
		$shellCmd = Process::fromShellCommandline("mkdir {$this->basePath}/{$cryptDir} && echo \"{$pass}\" | gocryptfs -init -q -- {$this->basePath}/{$cryptDir}");
		$shellCmd->run();
		if ($shellCmd->getExitCode() !== 0) {
			$this->logger->error($shellCmd->getErrorOutput());
			return false;
		}
		return true;
	}

	public function decrypt(string $pass, string $cryptDir): string {
		$mountDir = md5(random_bytes(100));
		$shellCmd = Process::fromShellCommandline("mkdir {$this->basePath}/{$mountDir} && echo \"{$pass}\" | gocryptfs {$this->basePath}/{$cryptDir} {$this->basePath}/{$mountDir}");
		dd($shellCmd);
		$shellCmd->run();
		if ($shellCmd->getExitCode() !== 0) {
			$shellCmd = Process::fromShellCommandline("rm -rf {$this->basePath}/{$mountDir}");
			$shellCmd->run();
			$this->logger->error($shellCmd->getErrorOutput());
			return '';
		}
		return $mountDir;
	}

	public function close(string $cryptDir, string $mountDir): bool {
		$shellCmd = Process::fromShellCommandline("umount {$this->basePath}/{$mountDir} && rm -rf {$this->basePath}/{$mountDir}");
		$shellCmd->run();
		if ($shellCmd->getExitCode() !== 0) {
			$this->logger->error($shellCmd->getErrorOutput());
			return false;
		}
		return true;
	}
}