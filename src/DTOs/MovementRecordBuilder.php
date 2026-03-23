<?php

declare(strict_types=1);

namespace Entelix\EdiLink\DTOs;

use DateTimeImmutable;

/**
 * MovementRecordBuilder
 *
 * Fluent builder for MovementRecord. Provides a clean, readable API
 * for constructing records from any data source.
 */
final class MovementRecordBuilder
{
    private array $data = [
        'containerNumber'          => '',
        'isoType'                  => '',
        'carrierCode'              => '',
        'reportingParty'           => '',
        'depotCode'                => '',
        'depotName'                => '',
        'carrierDepotCode'         => '',
        'carrierEventLocation'     => '',
        'zoneId'                   => 0,
        'arrivedAt'                => null,
        'arrivalMovementType'      => '',
        'arrivalVehicle'           => '',
        'arrivalTransporter'       => '',
        'originLocation'           => '',
        'deliveryOrderRef'         => '',
        'deliveryOrderExpiry'      => null,
        'deliveryOrderOverdue'     => false,
        'surveyedAt'               => null,
        'sentForRepairAt'          => null,
        'returnedFromRepairAt'     => null,
        'departedAt'               => null,
        'departureMovementType'    => '',
        'departureVehicle'         => '',
        'destinationLocation'      => '',
        'bookingRef'               => '',
        'bookingExpiry'            => null,
        'bookingOverdue'           => false,
        'consigneeName'            => '',
        'sealReference'            => '',
        'remarks'                  => '',
        'stuffedAt'                => null,
        'destuffedAt'              => null,
        'dispatchedGateIn'         => '',
        'dispatchedSurvey'         => '',
        'dispatchedMnrIn'          => '',
        'dispatchedMnrOut'         => '',
        'dispatchedGateOut'        => '',
        'dispatchedStuffing'       => '',
        'dispatchedDestuffing'     => '',
        'recordId'                 => '',
    ];

    // ── Fluent setters ────────────────────────────────────────────────────────

    public function identity(
        string $containerNumber,
        string $isoType,
        string $carrierCode,
        string $reportingParty = ''
    ): self {
        $this->data['containerNumber'] = strtoupper(trim($containerNumber));
        $this->data['isoType']         = strtoupper(trim($isoType));
        $this->data['carrierCode']     = strtoupper(trim($carrierCode));
        $this->data['reportingParty']  = $reportingParty;
        return $this;
    }

    public function depot(
        string $depotCode,
        string $depotName            = '',
        string $carrierDepotCode     = '',
        string $carrierEventLocation = '',
        int    $zone                 = 0
    ): self {
        $this->data['depotCode']              = strtoupper(trim($depotCode));
        $this->data['depotName']              = $depotName;
        $this->data['carrierDepotCode']       = $carrierDepotCode;
        $this->data['carrierEventLocation']   = $carrierEventLocation;
        $this->data['zoneId']                 = $zone;
        return $this;
    }

    public function arrival(
        string $timestamp,
        string $movementType   = '',
        string $vehicleNo      = '',
        string $transporter    = '',
        string $originLocation = ''
    ): self {
        $this->data['arrivedAt']           = $this->parseTimestamp($timestamp);
        $this->data['arrivalMovementType'] = $movementType;
        $this->data['arrivalVehicle']      = $vehicleNo;
        $this->data['arrivalTransporter']  = $transporter;
        $this->data['originLocation']      = $originLocation;
        return $this;
    }

    public function deliveryOrder(
        string $ref,
        string $validity = '',
        bool   $grace    = false
    ): self {
        $this->data['deliveryOrderRef']     = $ref;
        $this->data['deliveryOrderExpiry']  = $validity ? $this->parseTimestamp($validity) : null;
        $this->data['deliveryOrderOverdue'] = $grace;
        return $this;
    }

    public function survey(string $timestamp): self
    {
        $this->data['surveyedAt'] = $this->parseTimestamp($timestamp);
        return $this;
    }

    public function repairCycle(string $sentAt, string $returnedAt = ''): self
    {
        $this->data['sentForRepairAt']      = $this->parseTimestamp($sentAt);
        $this->data['returnedFromRepairAt'] = $returnedAt ? $this->parseTimestamp($returnedAt) : null;
        return $this;
    }

    public function departure(
        string $timestamp,
        string $movementType        = '',
        string $vehicleNo           = '',
        string $destinationLocation = ''
    ): self {
        $this->data['departedAt']            = $this->parseTimestamp($timestamp);
        $this->data['departureMovementType'] = $movementType;
        $this->data['departureVehicle']      = $vehicleNo;
        $this->data['destinationLocation']   = $destinationLocation;
        return $this;
    }

    public function booking(
        string $ref,
        string $validity  = '',
        bool   $grace     = false,
        string $consignee = '',
        string $sealRef   = '',
        string $remarks   = ''
    ): self {
        $this->data['bookingRef']     = $ref;
        $this->data['bookingExpiry']  = $validity ? $this->parseTimestamp($validity) : null;
        $this->data['bookingOverdue'] = $grace;
        $this->data['consigneeName']  = $consignee;
        $this->data['sealReference']  = $sealRef;
        $this->data['remarks']        = $remarks;
        return $this;
    }

    public function cfsOperations(string $stuffedAt = '', string $destuffedAt = ''): self
    {
        $this->data['stuffedAt']   = $stuffedAt   ? $this->parseTimestamp($stuffedAt)   : null;
        $this->data['destuffedAt'] = $destuffedAt ? $this->parseTimestamp($destuffedAt) : null;
        return $this;
    }

    /**
     * Set which EDI events have already been dispatched.
     * Pass the filename if dispatched, empty string if pending.
     */
    public function ediFlags(
        string $gateIn     = '',
        string $survey     = '',
        string $mnrIn      = '',
        string $mnrOut     = '',
        string $gateOut    = '',
        string $stuffing   = '',
        string $destuffing = ''
    ): self {
        $this->data['dispatchedGateIn']     = $gateIn;
        $this->data['dispatchedSurvey']     = $survey;
        $this->data['dispatchedMnrIn']      = $mnrIn;
        $this->data['dispatchedMnrOut']     = $mnrOut;
        $this->data['dispatchedGateOut']    = $gateOut;
        $this->data['dispatchedStuffing']   = $stuffing;
        $this->data['dispatchedDestuffing'] = $destuffing;
        return $this;
    }

    public function id(string $recordId): self
    {
        $this->data['recordId'] = $recordId;
        return $this;
    }

    /**
     * Finalize and return an immutable MovementRecord.
     */
    public function make(): MovementRecord
    {
        $d = $this->data;

        return new MovementRecord(
            containerNumber:       $d['containerNumber'],
            isoType:               $d['isoType'],
            carrierCode:           $d['carrierCode'],
            reportingParty:        $d['reportingParty'],
            depotCode:             $d['depotCode'],
            depotName:             $d['depotName'],
            carrierDepotCode:      $d['carrierDepotCode'],
            carrierEventLocation:  $d['carrierEventLocation'],
            zoneId:                $d['zoneId'],
            arrivedAt:             $d['arrivedAt'],
            arrivalMovementType:   $d['arrivalMovementType'],
            arrivalVehicle:        $d['arrivalVehicle'],
            arrivalTransporter:    $d['arrivalTransporter'],
            originLocation:        $d['originLocation'],
            deliveryOrderRef:      $d['deliveryOrderRef'],
            deliveryOrderExpiry:   $d['deliveryOrderExpiry'],
            deliveryOrderOverdue:  $d['deliveryOrderOverdue'],
            surveyedAt:            $d['surveyedAt'],
            sentForRepairAt:       $d['sentForRepairAt'],
            returnedFromRepairAt:  $d['returnedFromRepairAt'],
            departedAt:            $d['departedAt'],
            departureMovementType: $d['departureMovementType'],
            departureVehicle:      $d['departureVehicle'],
            destinationLocation:   $d['destinationLocation'],
            bookingRef:            $d['bookingRef'],
            bookingExpiry:         $d['bookingExpiry'],
            bookingOverdue:        $d['bookingOverdue'],
            consigneeName:         $d['consigneeName'],
            sealReference:         $d['sealReference'],
            remarks:               $d['remarks'],
            stuffedAt:             $d['stuffedAt'],
            destuffedAt:           $d['destuffedAt'],
            dispatchedGateIn:      $d['dispatchedGateIn'],
            dispatchedSurvey:      $d['dispatchedSurvey'],
            dispatchedMnrIn:       $d['dispatchedMnrIn'],
            dispatchedMnrOut:      $d['dispatchedMnrOut'],
            dispatchedGateOut:     $d['dispatchedGateOut'],
            dispatchedStuffing:    $d['dispatchedStuffing'],
            dispatchedDestuffing:  $d['dispatchedDestuffing'],
            recordId:              $d['recordId'],
        );
    }

    // ── Static factory from array ─────────────────────────────────────────────

    /**
     * Map a flat associative array (DB row / stdClass) to a builder.
     * Supports both snake_case (container_number) and camelCase (containerNumber).
     *
     * @param array<string, mixed> $row
     */
    public static function fromArray(array $row): self
    {
        $b = new self();

        $get = static function (array $row, string ...$keys): string {
            foreach ($keys as $key) {
                if (isset($row[$key]) && $row[$key] !== null && $row[$key] !== '') {
                    return (string) $row[$key];
                }
            }
            return '';
        };

        $getBool = static function (array $row, string ...$keys): bool {
            foreach ($keys as $key) {
                if (isset($row[$key])) {
                    $v = $row[$key];
                    if (is_bool($v)) return $v;
                    if (is_string($v)) return strtolower($v) === 'yes' || $v === '1';
                    if (is_int($v)) return $v === 1;
                }
            }
            return false;
        };

        return $b
            ->identity(
                $get($row, 'container_number', 'containerNumber', 'container_no'),
                $get($row, 'iso_type', 'isoType', 'container_type', 'containerType'),
                $get($row, 'carrier_code', 'carrierCode', 'shipping_line_code'),
                $get($row, 'reporting_party', 'reportingParty', 'edi_reporting_by')
            )
            ->depot(
                $get($row, 'depot_code', 'depotCode', 'company_code'),
                $get($row, 'depot_name', 'depotName'),
                $get($row, 'carrier_depot_code', 'carrierDepotCode', 'ova_depot_code'),
                $get($row, 'carrier_event_location', 'carrierEventLocation', 'ova_event_location'),
                (int) $get($row, 'zone_id', 'zoneId', 'zone')
            )
            ->arrival(
                $get($row, 'arrived_at', 'arrivedAt', 'gate_in', 'gate_in_at'),
                $get($row, 'arrival_movement_type', 'arrivalMovementType', 'in_movement_type'),
                $get($row, 'arrival_vehicle', 'arrivalVehicle', 'in_vehicle_no'),
                $get($row, 'arrival_transporter', 'arrivalTransporter', 'in_transporter'),
                $get($row, 'origin_location', 'originLocation', 'from_location')
            )
            ->deliveryOrder(
                $get($row, 'delivery_order_ref', 'deliveryOrderRef', 'do_no'),
                $get($row, 'delivery_order_expiry', 'deliveryOrderExpiry', 'do_validity'),
                $getBool($row, 'delivery_order_overdue', 'deliveryOrderOverdue', 'do_grace')
            )
            ->survey(
                $get($row, 'surveyed_at', 'surveyedAt', 'damage', 'damage_at')
            )
            ->repairCycle(
                $get($row, 'sent_for_repair_at', 'sentForRepairAt', 'mnr_in', 'mnr_in_at'),
                $get($row, 'returned_from_repair_at', 'returnedFromRepairAt', 'mnr_out', 'mnr_out_at')
            )
            ->departure(
                $get($row, 'departed_at', 'departedAt', 'gate_out', 'gate_out_at'),
                $get($row, 'departure_movement_type', 'departureMovementType', 'out_movement_type'),
                $get($row, 'departure_vehicle', 'departureVehicle', 'out_vehicle_no'),
                $get($row, 'destination_location', 'destinationLocation', 'to_location')
            )
            ->booking(
                $get($row, 'booking_ref', 'bookingRef', 'bo_no'),
                $get($row, 'booking_expiry', 'bookingExpiry', 'bo_validity'),
                $getBool($row, 'booking_overdue', 'bookingOverdue', 'bo_grace'),
                $get($row, 'consignee_name', 'consigneeName', 'shipper_name'),
                $get($row, 'seal_reference', 'sealReference', 'seal_no'),
                $get($row, 'remarks', 'notes')
            )
            ->cfsOperations(
                $get($row, 'stuffed_at', 'stuffedAt', 'stuffing'),
                $get($row, 'destuffed_at', 'destuffedAt', 'destuffing')
            )
            ->ediFlags(
                $get($row, 'dispatched_gate_in', 'dispatchedGateIn', 'edi_gate_in'),
                $get($row, 'dispatched_survey', 'dispatchedSurvey', 'edi_damage'),
                $get($row, 'dispatched_mnr_in', 'dispatchedMnrIn', 'edi_mnr_in'),
                $get($row, 'dispatched_mnr_out', 'dispatchedMnrOut', 'edi_mnr_out'),
                $get($row, 'dispatched_gate_out', 'dispatchedGateOut', 'edi_gate_out'),
                $get($row, 'dispatched_stuffing', 'dispatchedStuffing', 'edi_stuffing'),
                $get($row, 'dispatched_destuffing', 'dispatchedDestuffing', 'edi_destuffing')
            )
            ->id($get($row, 'record_id', 'recordId', 'id'));
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function parseTimestamp(string $value): ?DateTimeImmutable
    {
        if (empty($value)) return null;

        $formats = [
            'Y-m-d H:i:s',
            'd-m-Y H:i:s',
            'd-m-Y H:i',
            'Y-m-d',
            'd-m-Y',
        ];

        foreach ($formats as $fmt) {
            $dt = DateTimeImmutable::createFromFormat($fmt, $value);
            if ($dt !== false) return $dt;
        }

        $ts = strtotime($value);
        return $ts !== false ? new DateTimeImmutable('@' . $ts) : null;
    }
}
