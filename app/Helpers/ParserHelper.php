<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Models\Enum\ContractType;
use Illuminate\Support\Facades\Storage;
use Matronator\Parsem\Parser;

class ParserHelper
{
    public const string CONTRACTS_DIR = 'contracts/';

    public static function parse(string $template, array $arguments): string
    {
        return Parser::parseString($template, $arguments);
    }

    public static function getTemplate(string $filename): string
    {
        return Storage::get(self::CONTRACTS_DIR . $filename);
    }

    public static function getFilenameFromType(ContractType $type): string
    {
        return match ($type) {
            ContractType::TOKEN => 'token.clar.mtr',
        };
    }
}
