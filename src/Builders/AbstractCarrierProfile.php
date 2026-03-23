<?php

declare(strict_types=1);

namespace Entelix\EdiLink\Builders;

use DateTimeImmutable;
use Entelix\EdiLink\Contracts\CarrierProfileInterface;
use Entelix\EdiLink\Core\EdiOutput;
use Entelix\EdiLink\DTOs\MovementRecord;

/**
 * AbstractCarrierProfile
 *
 * Base class providing shared utilities for carrier profiles.
 *
 * Concrete profiles extend this and implement the build* methods.
 * Default no-op implementations for optional events (CFS, stuffing, destuffing)
 * return EdiOutput::empty() so they never need to be overridden unless needed.
 */
abstract class AbstractCarrierProfile implements CarrierProfileInterface
{
    protected string $outputFormat;

    public function __construct(string $outputFormat = 'text')
    {
        $this->outputFormat = $outputFormat;
    }

    // ── Optional events: default no-ops ───────────────────────────────────────

    public function buildCfsArrival(array $records): EdiOutput
    {
        return EdiOutput::empty('cfs_arrival');
    }

    public function buildStuffing(array $records): EdiOutput
    {
        return EdiOutput::empty('stuffing');
    }

    public function buildDestuffing(array $records): EdiOutput
    {
        return EdiOutput::empty('destuffing');
    }

    public function supportedFormats(): array
    {
        return ['text', 'array'];
    }

    // ── Orchestrator ──────────────────────────────────────────────────────────

    /**
     * Run all event builders. Concrete profiles override buildAll() only if
     * they need a different execution order.
     *
     * @param  MovementRecord[] $records
     * @return array<string, EdiOutput>
     */
    public function buildAll(array $records): array
    {
        return [
            'cfs_arrival'    => $this->buildCfsArrival($records),
            'gate_in'        => $this->buildGateIn($records),
            'survey'         => $this->buildSurvey($records),
            'repair_dispatch'=> $this->buildRepairDispatch($records),
            'repair_return'  => $this->buildRepairReturn($records),
            'stuffing'       => $this->buildStuffing($records),
            'destuffing'     => $this->buildDestuffing($records),
            'gate_out'       => $this->buildGateOut($records),
        ];
    }

    // ── Timestamp helpers ─────────────────────────────────────────────────────

    /**
     * Format a DateTimeImmutable to the standard EDI timestamp string.
     * Strips separators: "15-06-2024 09:30" → "150620240930"
     */
    protected function ediTimestamp(?DateTimeImmutable $dt): string
    {
        if ($dt === null) return '';
        return $dt->format('dmYHi');
    }

    /**
     * Format a DateTimeImmutable to a date-only EDI string: "15-06-2024" → "15062024"
     */
    protected function ediDate(?DateTimeImmutable $dt): string
    {
        if ($dt === null) return '';
        return $dt->format('dmY');
    }

    /**
     * True when the record's arrival is strictly before the survey,
     * which is strictly before repair dispatch, and so on.
     * Chronological integrity check before emitting any EDI.
     */
    protected function arrivalBeforeSurvey(MovementRecord $r): bool
    {
        if ($r->arrivedAt === null || $r->surveyedAt === null) return false;
        return $r->arrivedAt < $r->surveyedAt;
    }

    protected function surveyBeforeRepairDispatch(MovementRecord $r): bool
    {
        if ($r->surveyedAt === null || $r->sentForRepairAt === null) return false;
        return $r->surveyedAt < $r->sentForRepairAt;
    }

    protected function repairDispatchBeforeReturn(MovementRecord $r): bool
    {
        if ($r->sentForRepairAt === null || $r->returnedFromRepairAt === null) return false;
        return $r->sentForRepairAt < $r->returnedFromRepairAt;
    }

    protected function repairReturnBeforeDeparture(MovementRecord $r): bool
    {
        if ($r->returnedFromRepairAt === null || $r->departedAt === null) return false;
        return $r->returnedFromRepairAt < $r->departedAt;
    }

    /**
     * Full chain validation: arrival → survey → repair_in → repair_out → departure
     */
    protected function fullChainValid(MovementRecord $r): bool
    {
        return $this->arrivalBeforeSurvey($r)
            && $this->surveyBeforeRepairDispatch($r)
            && $this->repairDispatchBeforeReturn($r)
            && $this->repairReturnBeforeDeparture($r);
    }

    /**
     * Resolve an effective timestamp: if the overdue flag is set, use the
     * expiry date at end-of-day (23:55) instead of the actual event time.
     */
    protected function effectiveTimestamp(
        ?DateTimeImmutable $actual,
        ?DateTimeImmutable $expiry,
        bool $overdue
    ): ?DateTimeImmutable {
        if ($overdue && $expiry !== null) {
            return $expiry->setTime(23, 55, 0);
        }
        return $actual;
    }

    /**
     * True if the event has not yet been dispatched.
     * EDILink's dispatch flag is an empty string for "pending".
     */
    protected function isPending(string $dispatchFlag): bool
    {
        return trim($dispatchFlag) === '';
    }
}
