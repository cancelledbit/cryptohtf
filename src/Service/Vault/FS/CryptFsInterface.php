<?php

namespace App\Service\Vault\FS;

interface CryptFsInterface
{
    public function createStorage(string $pass, string $cryptDir): bool;

    public function changeSecret(string $oldPass, string $newPass, string $cryptDir): string;

    public function open(string $pass, string $cryptDir): string;

    public function close(string $cryptDir, string $mountDir): bool;

    public function remove(string $cryptDir): bool;
}
