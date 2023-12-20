<?php

namespace App\Service\Vault\Contract;

use App\Service\Vault\Key\KeyInterface;

interface VaultInterface
{
    public function unlock(KeyInterface $key): bool;

    public function lock(): bool;

    public function init(): string;

    public function remove(): bool;

    public function refresh(string $oldSecret): string;

    public function getPlainFolder(): string;

    public function getEncryptedFolder(): string;
}
