<?php

namespace App\Service\Vault\FS;

use Psr\Log\LoggerInterface;
use Symfony\Component\Process\Process;

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

	public function open(string $pass, string $cryptDir): string {
		$mountDir = md5(random_bytes(100));
		$shellCmd = Process::fromShellCommandline("mkdir {$this->basePath}/{$mountDir} && echo \"{$pass}\" | gocryptfs {$this->basePath}/{$cryptDir} {$this->basePath}/{$mountDir}");
		$shellCmd->run();
		if ($shellCmd->getExitCode() !== 0) {
            $this->logger->error($shellCmd->getErrorOutput());
			$shellCmd = Process::fromShellCommandline("rm -rf {$this->basePath}/{$mountDir}");
			$shellCmd->run();
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

    public function changeSecret(string $oldPass, string $newPass, string $cryptDir): string {
        $shellCmd = Process::fromShellCommandline("echo \"{$oldPass}\n{$newPass}\" | gocryptfs -passwd -q -- {$this->basePath}/{$cryptDir}");
        $shellCmd->run();
        if ($shellCmd->getExitCode() !== 0) {
            $this->logger->error($shellCmd->getErrorOutput());
            return '';
        }
        return $newPass;
    }

    public function remove(string $cryptDir): bool {
        if (
            !$cryptDir
            || str_contains($cryptDir, '..')
            || str_contains($cryptDir, './')
            || str_contains($cryptDir, '.')
        ) {
            throw new \UnexpectedValueException('Trying to remove fs');
        }
        $shellCmd = Process::fromShellCommandline("cd {$this->basePath} && rm -rf $cryptDir");
        $shellCmd->run();
        if ($shellCmd->getExitCode() !== 0) {
            $this->logger->error($shellCmd->getErrorOutput());
            return false;
        }
        return true;
    }
}