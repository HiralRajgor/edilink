<?php

declare(strict_types=1);

namespace Entelix\EdiLink\Facades;

use Illuminate\Support\Facades\Facade;
use Entelix\EdiLink\Contracts\CarrierProfileInterface;

/**
 * @method static CarrierProfileInterface carrier(string $code, string $format = 'text')
 * @method static string generate(string $carrierCode, array $records)
 * @method static \Entelix\EdiLink\EdiLink register(string $code, string $profileClass)
 * @method static string[] carriers()
 *
 * @see \Entelix\EdiLink\EdiLink
 */
class EdiLink extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'edilink';
    }
}
