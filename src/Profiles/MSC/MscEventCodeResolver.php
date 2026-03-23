<?php

declare(strict_types=1);

namespace Entelix\EdiLink\Profiles\MSC;

/**
 * MscEventCodeResolver
 *
 * Resolves MSC-specific EDI event codes from movement type strings.
 *
 * MSC uses different codes depending on the physical zone (port).
 * This class encapsulates all that logic in one place so the profile
 * class stays clean and readable.
 *
 * Zone IDs map to Indian port zones:
 *   15 → Hazira   26 → Mundra   29 → Nhava Sheva
 */
final class MscEventCodeResolver
{
    /**
     * Arrival event codes by movement type (default / all zones).
     * @var array<string, string>
     */
    private const ARRIVAL_CODES = [
        'FULL_IN'          => 'DEV',
        'EMPTY_RETURN'     => 'ERM',
        'LINE_TRANSFER_IN' => 'MPI',
        'EN_BLOC'          => 'MPI',
        'IMPORT_ARRIVAL'   => 'IIR',
        'REPAIR_RETURN'    => 'REP',
    ];

    /**
     * Departure event codes by movement type (default / all zones).
     * @var array<string, string>
     */
    private const DEPARTURE_CODES = [
        'FULL_OUT'          => 'FST',
        'RE_EXPORT'         => 'MPO',
        'LINE_TRANSFER_OUT' => 'MPO',
        'IMPORT_OUT'        => 'IOR',
        'FACTORY_DESTUFF'   => 'FDS',
    ];

    /**
     * Zone-specific overrides that replace the default code.
     * Structure: [ zone_id => [ movement_type => code ] ]
     *
     * @var array<int, array<string, string>>
     */
    private const ZONE_OVERRIDES = [
        15 => ['FULL_IN' => 'MCY', 'FULL_OUT' => 'MSH'],   // Hazira
        26 => ['FULL_IN' => 'MCY', 'FULL_OUT' => 'MSH'],   // Mundra
        29 => ['FULL_IN' => 'MCY', 'FULL_OUT' => 'MSH'],   // Nhava Sheva
    ];

    /**
     * Survey / damage event code — fixed for MSC.
     */
    public const SURVEY_CODE = 'DAM';

    /**
     * Repair dispatch code (MNR In — sent to workshop).
     */
    public const REPAIR_DISPATCH_CODE = 'TBR';

    /**
     * Repair return code (MNR Out — returned from workshop).
     */
    public const REPAIR_RETURN_CODE = 'REP';

    /**
     * CFS arrival code.
     */
    public const CFS_ARRIVAL_CODE = 'DVAN';

    /**
     * Stuffing code.
     */
    public const STUFFING_CODE = 'CST';

    /**
     * Destuffing code.
     */
    public const DESTUFFING_CODE = 'DST';

    /**
     * Resolve the arrival EDI event code for a given movement type and zone.
     */
    public function arrivalCode(string $movementType, int $zoneId = 0): string
    {
        // Check zone-specific override first
        if ($zoneId > 0 && isset(self::ZONE_OVERRIDES[$zoneId][$movementType])) {
            return self::ZONE_OVERRIDES[$zoneId][$movementType];
        }

        return self::ARRIVAL_CODES[$movementType] ?? $movementType;
    }

    /**
     * Resolve the departure EDI event code for a given movement type and zone.
     */
    public function departureCode(string $movementType, int $zoneId = 0): string
    {
        if ($zoneId > 0 && isset(self::ZONE_OVERRIDES[$zoneId][$movementType])) {
            return self::ZONE_OVERRIDES[$zoneId][$movementType];
        }

        return self::DEPARTURE_CODES[$movementType] ?? $movementType;
    }

    /**
     * True when the given departure movement type includes a destination location.
     * e.g. RE_EXPORT requires a "to location" field in the EDI line.
     */
    public function departureRequiresDestination(string $movementType): bool
    {
        return in_array($movementType, ['RE_EXPORT', 'LINE_TRANSFER_OUT'], true);
    }

    /**
     * True when departure is a booking-based movement (needs booking ref).
     */
    public function departureUsesBooking(string $movementType): bool
    {
        return in_array($movementType, [
            'FULL_OUT', 'FACTORY_DESTUFF',
        ], true);
    }

    /**
     * All registered arrival movement types.
     * @return string[]
     */
    public function knownArrivalTypes(): array
    {
        return array_keys(self::ARRIVAL_CODES);
    }

    /**
     * All registered departure movement types.
     * @return string[]
     */
    public function knownDepartureTypes(): array
    {
        return array_keys(self::DEPARTURE_CODES);
    }
}
