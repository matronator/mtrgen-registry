<?php

declare(strict_types=1);

namespace App\Models\Enum;

enum ChainType: string
{
    case MAIN = 'mainnet';
    case TEST = 'testnet';
}
