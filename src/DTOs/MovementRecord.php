<?php

declare(strict_types=1);

namespace Entelix\EdiLink\DTOs;

/**
 * MovementRecord
 *
 * Represents a single container's reportable state at the time of EDI generation.
 * This is the single input object that flows through the entire EDILink pipeline.
 *
 * Design philosophy:
 *   - Immutable after construction (readonly properties on PHP 8.2+)
 *   - No framework, no DB, no ORM references — pure domain data
 *   - Fluent builder via MovementRecord::build() for clean call sites
 *   - Timestamps stored as nullable \DateTimeImmutable for type safety
 *
 * Usage:
 *   $record = MovementRecord::build()
 *       ->identity('CMAU1234560', '20GP', 'MSC')
 *       ->depot('ADPT', depotCode: 'ADN01', zone: 26)
 *       ->arrival('2024-06-01 08:00:00', movementType: 'FULL_IN', vehicleNo: 'GJ05TX9999')
 *       ->deliveryOrder('MSCDOABCD123', validity: '2024-06-10', grace: false)
 *       ->ediFlags(gateIn: '', damage: '', mnrIn: '', mnrOut: '', gateOut: '')
 *       ->make();
 */
final class MovementRecord
{
    // ── Container identity ────────────────────────────────────────────────────
    public readonly string $containerNumber;
    public readonly string $isoType;             // e.g. "20GP", "40HC", "45G1"
    public readonly string $carrierCode;         // e.g. "MSC", "HLL"
    public readonly string $reportingParty;      // depot/yard identifier for EDI header

    // ── Depot / location ──────────────────────────────────────────────────────
    public readonly string $depotCode;           // short code used in EDI lines
    public readonly string $depotName;           // human name (for Excel output)
    public readonly string $carrierDepotCode;    // carrier-assigned depot code (e.g. MSC OVA code)
    public readonly string $carrierEventLocation;
    public readonly int    $zoneId;              // drives zone-specific carrier code overrides

    // ── Inbound leg ───────────────────────────────────────────────────────────
    public readonly ?\DateTimeImmutable $arrivedAt;
    public readonly string              $arrivalMovementType; // raw movement string from your app
    public readonly string              $arrivalVehicle;
    public readonly string              $arrivalTransporter;
    public readonly string              $originLocation;      // from-location for CFS routing

    // ── Delivery order ────────────────────────────────────────────────────────
    public readonly string              $deliveryOrderRef;
    public readonly ?\DateTimeImmutable $deliveryOrderExpiry;
    public readonly bool                $deliveryOrderOverdue; // grace period flag

    // ── Survey / damage ───────────────────────────────────────────────────────
    public readonly ?\DateTimeImmutable $surveyedAt;

    // ── Repair cycle ──────────────────────────────────────────────────────────
    public readonly ?\DateTimeImmutable $sentForRepairAt;     // MNR In
    public readonly ?\DateTimeImmutable $returnedFromRepairAt; // MNR Out

    // ── Outbound leg ──────────────────────────────────────────────────────────
    public readonly ?\DateTimeImmutable $departedAt;
    public readonly string              $departureMovementType;
    public readonly string              $departureVehicle;
    public readonly string              $destinationLocation;
    public readonly string              $bookingRef;
    public readonly ?\DateTimeImmutable $bookingExpiry;
    public readonly bool                $bookingOverdue;
    public readonly string              $consigneeName;
    public readonly string              $sealReference;
    public readonly string              $remarks;

    // ── Stuffing / destuffing (CFS depots) ────────────────────────────────────
    public readonly ?\DateTimeImmutable $stuffedAt;
    public readonly ?\DateTimeImmutable $destuffedAt;

    // ── EDI dispatch flags ────────────────────────────────────────────────────
    // Non-empty string = filename of the EDI file this was already reported in.
    // Empty string     = not yet reported, must be included in next EDI run.
    public readonly string $dispatchedGateIn;
    public readonly string $dispatchedSurvey;
    public readonly string $dispatchedMnrIn;
    public readonly string $dispatchedMnrOut;
    public readonly string $dispatchedGateOut;
    public readonly string $dispatchedStuffing;
    public readonly string $dispatchedDestuffing;

    // ── Internal identifier ───────────────────────────────────────────────────
    public readonly string $recordId; // your primary key — returned in EdiOutput for DB updates

    /**
     * Public constructor — all properties injected directly.
     * Use MovementRecord::build() or MovementRecord::fromArray() at call sites.
     * The builder calls this constructor; nothing else should need to.
     */
    public function __construct(
        string               $containerNumber,
        string               $isoType,
        string               $carrierCode,
        string               $reportingParty,
        string               $depotCode,
        string               $depotName,
        string               $carrierDepotCode,
        string               $carrierEventLocation,
        int                  $zoneId,
        ?\DateTimeImmutable  $arrivedAt,
        string               $arrivalMovementType,
        string               $arrivalVehicle,
        string               $arrivalTransporter,
        string               $originLocation,
        string               $deliveryOrderRef,
        ?\DateTimeImmutable  $deliveryOrderExpiry,
        bool                 $deliveryOrderOverdue,
        ?\DateTimeImmutable  $surveyedAt,
        ?\DateTimeImmutable  $sentForRepairAt,
        ?\DateTimeImmutable  $returnedFromRepairAt,
        ?\DateTimeImmutable  $departedAt,
        string               $departureMovementType,
        string               $departureVehicle,
        string               $destinationLocation,
        string               $bookingRef,
        ?\DateTimeImmutable  $bookingExpiry,
        bool                 $bookingOverdue,
        string               $consigneeName,
        string               $sealReference,
        string               $remarks,
        ?\DateTimeImmutable  $stuffedAt,
        ?\DateTimeImmutable  $destuffedAt,
        string               $dispatchedGateIn,
        string               $dispatchedSurvey,
        string               $dispatchedMnrIn,
        string               $dispatchedMnrOut,
        string               $dispatchedGateOut,
        string               $dispatchedStuffing,
        string               $dispatchedDestuffing,
        string               $recordId,
    ) {
        $this->containerNumber      = $containerNumber;
        $this->isoType              = $isoType;
        $this->carrierCode          = $carrierCode;
        $this->reportingParty       = $reportingParty;
        $this->depotCode            = $depotCode;
        $this->depotName            = $depotName;
        $this->carrierDepotCode     = $carrierDepotCode;
        $this->carrierEventLocation = $carrierEventLocation;
        $this->zoneId               = $zoneId;
        $this->arrivedAt            = $arrivedAt;
        $this->arrivalMovementType  = $arrivalMovementType;
        $this->arrivalVehicle       = $arrivalVehicle;
        $this->arrivalTransporter   = $arrivalTransporter;
        $this->originLocation       = $originLocation;
        $this->deliveryOrderRef     = $deliveryOrderRef;
        $this->deliveryOrderExpiry  = $deliveryOrderExpiry;
        $this->deliveryOrderOverdue = $deliveryOrderOverdue;
        $this->surveyedAt           = $surveyedAt;
        $this->sentForRepairAt      = $sentForRepairAt;
        $this->returnedFromRepairAt = $returnedFromRepairAt;
        $this->departedAt           = $departedAt;
        $this->departureMovementType = $departureMovementType;
        $this->departureVehicle     = $departureVehicle;
        $this->destinationLocation  = $destinationLocation;
        $this->bookingRef           = $bookingRef;
        $this->bookingExpiry        = $bookingExpiry;
        $this->bookingOverdue       = $bookingOverdue;
        $this->consigneeName        = $consigneeName;
        $this->sealReference        = $sealReference;
        $this->remarks              = $remarks;
        $this->stuffedAt            = $stuffedAt;
        $this->destuffedAt          = $destuffedAt;
        $this->dispatchedGateIn     = $dispatchedGateIn;
        $this->dispatchedSurvey     = $dispatchedSurvey;
        $this->dispatchedMnrIn      = $dispatchedMnrIn;
        $this->dispatchedMnrOut     = $dispatchedMnrOut;
        $this->dispatchedGateOut    = $dispatchedGateOut;
        $this->dispatchedStuffing   = $dispatchedStuffing;
        $this->dispatchedDestuffing = $dispatchedDestuffing;
        $this->recordId             = $recordId;
    }

    /**
     * Start a fluent builder chain.
     */
    public static function build(): MovementRecordBuilder
    {
        return new MovementRecordBuilder();
    }

    /**
     * Convenience factory — hydrate from any associative array.
     * Keys may be snake_case or camelCase.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return MovementRecordBuilder::fromArray($data)->make();
    }
}
