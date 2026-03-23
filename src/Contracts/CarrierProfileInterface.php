<?php

declare(strict_types=1);

namespace Entelix\EdiLink\Contracts;

use Entelix\EdiLink\Core\EdiOutput;
use Entelix\EdiLink\DTOs\MovementRecord;

/**
 * CarrierProfileInterface
 *
 * The contract every shipping line profile must satisfy.
 *
 * Each method accepts a list of MovementRecord objects and returns an EdiOutput
 * containing the generated content and the IDs of records that were included.
 *
 * Profiles are responsible for:
 *   - Their own carrier-specific field layout and widths
 *   - Their own event code mappings
 *   - Their own chronological validation rules (e.g. survey must follow arrival)
 *   - Skipping records that have already been dispatched
 *
 * Profiles are NOT responsible for:
 *   - Database queries or updates
 *   - File I/O
 *   - Email / SFTP delivery
 *   - Scheduling
 *
 * @example
 *   $profile = new MscCarrierProfile();
 *   $output  = $profile->buildGateIn($records);
 *   file_put_contents('output.edi', $output->content);
 */
interface CarrierProfileInterface
{
    /**
     * Short, uppercase carrier code. Used as the profile registry key.
     * e.g. "MSC", "HLL", "KMTC", "OOCL"
     */
    public function carrierCode(): string;

    /**
     * Full carrier name for display purposes.
     * e.g. "Mediterranean Shipping Company"
     */
    public function carrierName(): string;

    /**
     * Supported output formats for this carrier.
     * e.g. ['text', 'array']
     */
    public function supportedFormats(): array;

    /**
     * Gate-in: container arriving at the depot.
     *
     * @param MovementRecord[] $records
     */
    public function buildGateIn(array $records): EdiOutput;

    /**
     * Survey/Damage: container found damaged on arrival.
     *
     * @param MovementRecord[] $records
     */
    public function buildSurvey(array $records): EdiOutput;

    /**
     * Repair dispatch: container sent out for repair (MNR In).
     *
     * @param MovementRecord[] $records
     */
    public function buildRepairDispatch(array $records): EdiOutput;

    /**
     * Repair return: container back from repair workshop (MNR Out).
     *
     * @param MovementRecord[] $records
     */
    public function buildRepairReturn(array $records): EdiOutput;

    /**
     * Gate-out: container leaving the depot.
     *
     * @param MovementRecord[] $records
     */
    public function buildGateOut(array $records): EdiOutput;

    /**
     * CFS arrival: container received from a Container Freight Station.
     * Return EdiOutput::empty('cfs_arrival') if not applicable for this carrier.
     *
     * @param MovementRecord[] $records
     */
    public function buildCfsArrival(array $records): EdiOutput;

    /**
     * Stuffing: cargo stuffed into container at CFS.
     * Return EdiOutput::empty('stuffing') if not applicable.
     *
     * @param MovementRecord[] $records
     */
    public function buildStuffing(array $records): EdiOutput;

    /**
     * Destuffing: cargo removed from container at CFS.
     * Return EdiOutput::empty('destuffing') if not applicable.
     *
     * @param MovementRecord[] $records
     */
    public function buildDestuffing(array $records): EdiOutput;

    /**
     * Run all applicable event builders and return a map of results.
     * Keys are event type slugs matching the build* method names.
     *
     * @param  MovementRecord[] $records
     * @return array<string, EdiOutput>
     */
    public function buildAll(array $records): array;
}
