<?php

namespace App\Service\Vault;

use Artgris\Bundle\FileManagerBundle\Service\CustomConfServiceInterface;
use Symfony\Bundle\SecurityBundle\Security;

readonly class VaultBrowserService implements CustomConfServiceInterface
{
    private Vault $vault;

    public function __construct(private VaultFactory $vaultFactory, private Security $security)
    {
        $this->vault = $this->vaultFactory->getBySecurityContext($this->security);
        if (!$this->vault->isOpen()) {
            throw new \UnexpectedValueException('Vault is not open yet');
        }
    }

    /**
     * @param array<string, string> $extra
     *
     * @return array<string, ?string>
     */
    public function getConf($extra)
    {
        return [
            'dir' => $this->vault->getPlainFolder(),
            'show_file_count' => true,
        ];
    }
}
