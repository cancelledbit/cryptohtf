<?php

namespace App\Service\Vault;

use Artgris\Bundle\FileManagerBundle\Service\CustomConfServiceInterface;

class VaultBrowserService implements CustomConfServiceInterface {
	public function __construct(private readonly VaultFiles $manager) {
		if (!$this->manager->isOpen()) {
			throw new \UnexpectedValueException('Vault is not open yet');
		}
	}

	public function getConf($extra) {
		[
			'dir' => $this->manager->getMount(),
			'web_dir' => '/'
		];
	}
}