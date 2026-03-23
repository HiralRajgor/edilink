<?php

declare(strict_types=1);

namespace Entelix\EdiLink\Profiles\MSC;

use DateTimeImmutable;
use Entelix\EdiLink\Builders\AbstractCarrierProfile;
use Entelix\EdiLink\Core\EdiLine;
use Entelix\EdiLink\Core\EdiOutput;
use Entelix\EdiLink\DTOs\MovementRecord;

/**
 * MscCarrierProfile
 *
 * EDILink profile for Mediterranean Shipping Company (MSC).
 *
 * Each build*() method:
 *   1. Filters records with a pending dispatch flag for that event
 *   2. Validates chronological integrity
 *   3. Builds one EdiLine per eligible record using MscLineSchema::blank()->set(...)
 *   4. Returns an EdiOutput with content + includedIds for DB feedback
 *
 * Zero database coupling — the calling app updates its own DB using includedIds.
 */
final class MscCarrierProfile extends AbstractCarrierProfile
{
    private MscEventCodeResolver $codeResolver;

    public function __construct(string $outputFormat = 'text')
    {
        parent::__construct($outputFormat);
        $this->codeResolver = new MscEventCodeResolver();
    }

    public function carrierCode(): string { return 'MSC'; }
    public function carrierName(): string { return 'Mediterranean Shipping Company'; }

    // ── Gate In ───────────────────────────────────────────────────────────────

    public function buildGateIn(array $records): EdiOutput
    {
        $lines       = [];
        $rows        = [];
        $includedIds = [];

        foreach ($records as $record) {
            if (! $this->isPending($record->dispatchedGateIn)) continue;
            if ($record->arrivedAt === null) continue;
            if ($record->arrivalMovementType === 'DOCK_DESTUFF') continue;

            $eventCode = $this->codeResolver->arrivalCode(
                $record->arrivalMovementType,
                $record->zoneId
            );

            $effectiveArrival = $this->effectiveTimestamp(
                $record->arrivedAt,
                $record->deliveryOrderExpiry,
                $record->deliveryOrderOverdue
            );

            $line = MscLineSchema::blank()
                ->set('carrier_code',     $record->carrierCode)
                ->set('container_number', $record->containerNumber)
                ->set('iso_type',         $record->isoType)
                ->set('event_code',       $eventCode)
                ->set('event_timestamp',  $this->ediTimestamp($effectiveArrival))
                ->set('current_location', $record->depotCode)
                ->set('reference_number', $record->deliveryOrderRef)
                ->set('vehicle_number',   $record->arrivalVehicle)
                ->set('reporting_party',  $record->reportingParty)
                ->set('report_date',      (new DateTimeImmutable())->format('dmY'));

            $this->appendOutput($line, $lines, $rows);
            $includedIds[] = $record->recordId;
        }

        return $this->compileOutput($lines, $rows, $includedIds, 'gate_in');
    }

    // ── Survey / Damage ───────────────────────────────────────────────────────

    public function buildSurvey(array $records): EdiOutput
    {
        $lines       = [];
        $rows        = [];
        $includedIds = [];

        foreach ($records as $record) {
            if (! $this->isPending($record->dispatchedSurvey)) continue;
            if ($record->surveyedAt === null) continue;
            if (! $this->arrivalBeforeSurvey($record)) continue;

            $line = MscLineSchema::blank()
                ->set('carrier_code',     $record->carrierCode)
                ->set('container_number', $record->containerNumber)
                ->set('iso_type',         $record->isoType)
                ->set('event_code',       MscEventCodeResolver::SURVEY_CODE)
                ->set('event_timestamp',  $this->ediTimestamp($record->surveyedAt))
                ->set('current_location', $record->depotCode)
                ->set('reporting_party',  $record->reportingParty)
                ->set('report_date',      (new DateTimeImmutable())->format('dmY'))
                ->set('remarks',          $record->remarks ?? '');

            $this->appendOutput($line, $lines, $rows);
            $includedIds[] = $record->recordId;
        }

        return $this->compileOutput($lines, $rows, $includedIds, 'survey');
    }

    // ── Repair Dispatch (MNR In) ──────────────────────────────────────────────

    public function buildRepairDispatch(array $records): EdiOutput
    {
        $lines       = [];
        $rows        = [];
        $includedIds = [];

        foreach ($records as $record) {
            if (! $this->isPending($record->dispatchedMnrIn)) continue;
            if ($record->sentForRepairAt === null) continue;
            if (! $this->arrivalBeforeSurvey($record)) continue;
            if (! $this->surveyBeforeRepairDispatch($record)) continue;

            $line = MscLineSchema::blank()
                ->set('carrier_code',     $record->carrierCode)
                ->set('container_number', $record->containerNumber)
                ->set('iso_type',         $record->isoType)
                ->set('event_code',       MscEventCodeResolver::REPAIR_DISPATCH_CODE)
                ->set('event_timestamp',  $this->ediTimestamp($record->sentForRepairAt))
                ->set('current_location', $record->depotCode)
                ->set('reporting_party',  $record->reportingParty)
                ->set('report_date',      (new DateTimeImmutable())->format('dmY'));

            $this->appendOutput($line, $lines, $rows);
            $includedIds[] = $record->recordId;
        }

        return $this->compileOutput($lines, $rows, $includedIds, 'repair_dispatch');
    }

    // ── Repair Return (MNR Out) ───────────────────────────────────────────────

    public function buildRepairReturn(array $records): EdiOutput
    {
        $lines       = [];
        $rows        = [];
        $includedIds = [];

        foreach ($records as $record) {
            if (! $this->isPending($record->dispatchedMnrOut)) continue;
            if ($record->returnedFromRepairAt === null) continue;
            if (! $this->arrivalBeforeSurvey($record)) continue;
            if (! $this->surveyBeforeRepairDispatch($record)) continue;
            if (! $this->repairDispatchBeforeReturn($record)) continue;

            $line = MscLineSchema::blank()
                ->set('carrier_code',     $record->carrierCode)
                ->set('container_number', $record->containerNumber)
                ->set('iso_type',         $record->isoType)
                ->set('event_code',       MscEventCodeResolver::REPAIR_RETURN_CODE)
                ->set('event_timestamp',  $this->ediTimestamp($record->returnedFromRepairAt))
                ->set('current_location', $record->depotCode)
                ->set('reporting_party',  $record->reportingParty)
                ->set('report_date',      (new DateTimeImmutable())->format('dmY'));

            $this->appendOutput($line, $lines, $rows);
            $includedIds[] = $record->recordId;
        }

        return $this->compileOutput($lines, $rows, $includedIds, 'repair_return');
    }

    // ── Gate Out ──────────────────────────────────────────────────────────────

    public function buildGateOut(array $records): EdiOutput
    {
        $lines       = [];
        $rows        = [];
        $includedIds = [];

        foreach ($records as $record) {
            if (! $this->isPending($record->dispatchedGateOut)) continue;
            if ($record->departedAt === null) continue;
            if (! $this->fullChainValid($record)) continue;

            $eventCode = $this->codeResolver->departureCode(
                $record->departureMovementType,
                $record->zoneId
            );

            $effectiveDeparture = $this->effectiveTimestamp(
                $record->departedAt,
                $record->bookingExpiry,
                $record->bookingOverdue
            );

            $nextLocation = $this->codeResolver->departureRequiresDestination(
                $record->departureMovementType
            ) ? substr($record->destinationLocation, 0, 5) : '';

            $bookingRef = $this->codeResolver->departureUsesBooking($record->departureMovementType)
                ? substr($record->bookingRef, 0, 12)
                : $record->bookingRef;

            $line = MscLineSchema::blank()
                ->set('carrier_code',     $record->carrierCode)
                ->set('container_number', $record->containerNumber)
                ->set('iso_type',         $record->isoType)
                ->set('event_code',       $eventCode)
                ->set('event_timestamp',  $this->ediTimestamp($effectiveDeparture))
                ->set('current_location', $record->depotCode)
                ->set('next_location',    $nextLocation)
                ->set('reference_number', $bookingRef)
                ->set('consignee',        substr($record->consigneeName, 0, 8))
                ->set('vehicle_number',   $record->departureVehicle)
                ->set('reporting_party',  $record->reportingParty)
                ->set('report_date',      (new DateTimeImmutable())->format('dmY'))
                ->set('auxiliary_ref',    $record->sealReference);

            $this->appendOutput($line, $lines, $rows);
            $includedIds[] = $record->recordId;
        }

        return $this->compileOutput($lines, $rows, $includedIds, 'gate_out');
    }

    // ── CFS Arrival ───────────────────────────────────────────────────────────

    public function buildCfsArrival(array $records): EdiOutput
    {
        $lines       = [];
        $rows        = [];
        $includedIds = [];

        foreach ($records as $record) {
            if (! $this->isPending($record->dispatchedGateIn)) continue;
            if ($record->arrivedAt === null) continue;
            if (empty($record->originLocation)) continue;

            $reportTime = $record->arrivedAt->modify('-2 hours');
            if ($record->deliveryOrderOverdue && $record->deliveryOrderExpiry !== null) {
                $reportTime = $record->deliveryOrderExpiry->setTime(23, 55);
            }

            $line = MscLineSchema::blank()
                ->set('carrier_code',     $record->carrierCode)
                ->set('container_number', $record->containerNumber)
                ->set('iso_type',         $record->isoType)
                ->set('event_code',       MscEventCodeResolver::CFS_ARRIVAL_CODE)
                ->set('event_timestamp',  $this->ediTimestamp($reportTime))
                ->set('current_location', substr($record->originLocation, 0, 5))
                ->set('reporting_party',  $record->reportingParty);

            $this->appendOutput($line, $lines, $rows);
            $includedIds[] = $record->recordId;
        }

        return $this->compileOutput($lines, $rows, $includedIds, 'cfs_arrival');
    }

    // ── Stuffing ──────────────────────────────────────────────────────────────

    public function buildStuffing(array $records): EdiOutput
    {
        $lines       = [];
        $rows        = [];
        $includedIds = [];

        foreach ($records as $record) {
            if (! $this->isPending($record->dispatchedStuffing)) continue;
            if ($record->stuffedAt === null) continue;
            if ($record->arrivedAt === null || $record->arrivedAt >= $record->stuffedAt) continue;

            $line = MscLineSchema::blank()
                ->set('carrier_code',     $record->carrierCode)
                ->set('container_number', $record->containerNumber)
                ->set('iso_type',         $record->isoType)
                ->set('event_code',       MscEventCodeResolver::STUFFING_CODE)
                ->set('event_timestamp',  $this->ediTimestamp($record->stuffedAt))
                ->set('current_location', $record->depotCode)
                ->set('reporting_party',  $record->reportingParty)
                ->set('report_date',      (new DateTimeImmutable())->format('dmY'));

            $this->appendOutput($line, $lines, $rows);
            $includedIds[] = $record->recordId;
        }

        return $this->compileOutput($lines, $rows, $includedIds, 'stuffing');
    }

    // ── Destuffing ────────────────────────────────────────────────────────────

    public function buildDestuffing(array $records): EdiOutput
    {
        $lines       = [];
        $rows        = [];
        $includedIds = [];

        foreach ($records as $record) {
            if (! $this->isPending($record->dispatchedDestuffing)) continue;
            if ($record->destuffedAt === null) continue;
            if ($record->arrivedAt === null || $record->arrivedAt >= $record->destuffedAt) continue;

            $line = MscLineSchema::blank()
                ->set('carrier_code',     $record->carrierCode)
                ->set('container_number', $record->containerNumber)
                ->set('iso_type',         $record->isoType)
                ->set('event_code',       MscEventCodeResolver::DESTUFFING_CODE)
                ->set('event_timestamp',  $this->ediTimestamp($record->destuffedAt))
                ->set('current_location', $record->depotCode)
                ->set('reporting_party',  $record->reportingParty)
                ->set('report_date',      (new DateTimeImmutable())->format('dmY'));

            $this->appendOutput($line, $lines, $rows);
            $includedIds[] = $record->recordId;
        }

        return $this->compileOutput($lines, $rows, $includedIds, 'destuffing');
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function appendOutput(EdiLine $line, array &$lines, array &$rows): void
    {
        if ($this->outputFormat === 'array') {
            $rows[] = $line->toArray();
        } else {
            $lines[] = $line->toText();
        }
    }

    private function compileOutput(
        array  $lines,
        array  $rows,
        array  $includedIds,
        string $eventType
    ): EdiOutput {
        $content = $this->outputFormat === 'array'
            ? json_encode($rows, JSON_THROW_ON_ERROR)
            : implode('', $lines);

        return new EdiOutput(
            content:     $content,
            includedIds: $includedIds,
            eventType:   $eventType,
            rows:        $rows,
            generatedAt: new DateTimeImmutable()
        );
    }
}
