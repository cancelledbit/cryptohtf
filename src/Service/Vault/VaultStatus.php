<?php

namespace App\Service\Vault;

enum VaultStatus
{
    case OPEN;
    case ENCRYPTED;
    case NONE;
    case NON_EXISTENT;
}
