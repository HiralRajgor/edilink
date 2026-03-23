<?php

declare(strict_types=1);

namespace Entelix\EdiLink;

use Entelix\EdiLink\Contracts\CarrierProfileInterface;
use Entelix\EdiLink\Core\EdiOutput;
use Entelix\EdiLink\DTOs\MovementRecord;
use InvalidArgumentException;

/**
 * EdiLink
 *
 * Main entry point. Manages the registry of carrier profiles
 * and provides the fluent API used by applications.
 *
 * Via Facade:
 *   EdiLink::carrier('MSC')->buildAll($records);
 *   EdiLink::carrier('MSC')->buildGateIn($records);
 *   EdiLink::generate('MSC', $records);  // shorthand — full concatenated string
 *
 * Direct:
 *   $ediLink = new EdiLink();
 *   $ediLink->carrier('HLL', 'array')->buildGateIn($records);
 */
class EdiLink
{
    /** @var array<string, class-string<CarrierProfileInterface>> */
    private array $registry = [];

    public function __construct()
    {
        // Register built-in profiles
        $this->register('MSC', \Entelix\EdiLink\Profiles\MSC\MscCarrierProfile::class);
    }

    /**
     * Register a carrier profile class.
     *
     * @param string $code  Uppercase carrier code, e.g. "HLL"
     * @param class-string<CarrierProfileInterface> $profileClass
     */
    public function register(string $code, string $profileClass): self
    {
        $this->registry[strtoupper($code)] = $profileClass;
        return $this;
    }

    /**
     * Resolve a carrier profile instance.
     *
     * @param  string $code    e.g. "MSC"
     * @param  string $format  'text' (default) or 'array'
     * @throws InvalidArgumentException
     */
    public function carrier(string $code, string $format = 'text'): CarrierProfileInterface
    {
        $key = strtoupper($code);

        if (! array_key_exists($key, $this->registry)) {
            throw new InvalidArgumentException(sprintf(
                'EDILink: No carrier profile registered for [%s]. Registered carriers: %s',
                $key,
                implode(', ', array_keys($this->registry))
            ));
        }

        return new $this->registry[$key]($format);
    }

    /**
     * Generate a complete EDI string for all events in one call.
     * Internally calls buildAll() on the profile and concatenates text content.
     *
     * @param  string           $carrierCode
     * @param  MovementRecord[] $records
     */
    public function generate(string $carrierCode, array $records): string
    {
        $outputs = $this->carrier($carrierCode)->buildAll($records);

        return implode('', array_map(
            fn(EdiOutput $o) => $o->content,
            $outputs
        ));
    }

    /**
     * List all registered carrier codes.
     * @return string[]
     */
    public function carriers(): array
    {
        return array_keys($this->registry);
    }
}
