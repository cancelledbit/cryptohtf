<?php

namespace App\Service\Vault;

use Artgris\Bundle\FileManagerBundle\Service\CustomConfServiceInterface;

class VaultBrowserService implements CustomConfServiceInterface {
	public function __construct(private readonly VaultFiles $files) {
		if (!$this->files->isOpen()) {
			throw new \UnexpectedValueException('Vault is not open yet');
		}
	}

	public function getConf($extra) {
		return [
			'dir' => $this->files->getMount(),
            'show_file_count' => true,
		];
	}
}