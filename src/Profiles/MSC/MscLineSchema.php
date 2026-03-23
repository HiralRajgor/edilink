<?php

declare(strict_types=1);

namespace Entelix\EdiLink\Profiles\MSC;

use Entelix\EdiLink\Core\EdiLine;

/**
 * MscLineSchema
 *
 * Defines the field layout for MSC fixed-width EDI lines.
 *
 * MSC's format is a single fixed-width line per container event,
 * with all fields concatenated in a defined order.
 *
 * Having the schema in its own class means:
 *   - The profile class stays clean
 *   - Field widths are defined in one place
 *   - New fields can be added without touching the generation logic
 *   - Tests can validate the schema independently
 *
 * Total line width: 228 characters (+ CRLF)
 *
 * Field map:
 *   carrier_code       5    Shipping line identifier
 *   container_number   15   ISO container number
 *   iso_type           10   Size/type code
 *   event_code         10   MSC event code (DAM, TBR, FST, etc.)
 *   event_timestamp    13   ddMMyyyyHHmm
 *   current_location   5    Depot code
 *   next_location      5    Destination (for RE_EXPORT only)
 *   reference_number   25   DO or booking reference
 *   consignee          10   Shipper / consignee short name
 *   transporter        10   Transport company (optional)
 *   vehicle_number     25   Truck registration
 *   condition          1    Container condition flag
 *   reporting_party    10   Depot reporting identifier
 *   report_date        8    ddMMyyyy
 *   remarks            50   Free text
 *   transport_mode     1    R=Road, T=Train
 *   auxiliary_ref      25   Seal number / job order
 */
final class MscLineSchema
{
    /**
     * Build a blank EdiLine pre-populated with all MSC fields at correct widths.
     * Caller fills in the values via fluent field overrides.
     */
    public static function blank(): EdiLine
    {
        return EdiLine::make()
            ->add('carrier_code',      5,  '')
            ->add('container_number',  15, '')
            ->add('iso_type',          10, '')
            ->add('event_code',        10, '')
            ->add('event_timestamp',   13, '')
            ->add('current_location',  5,  '')
            ->add('next_location',     5,  '')
            ->add('reference_number',  25, '')
            ->add('consignee',         10, '')
            ->add('transporter',       10, '')
            ->add('vehicle_number',    25, '')
            ->add('condition',         1,  '')
            ->add('reporting_party',   10, '')
            ->add('report_date',       8,  '')
            ->add('remarks',           50, '')
            ->add('transport_mode',    1,  'R')
            ->add('auxiliary_ref',     25, '');
    }

    /**
     * Total expected character width for validation.
     */
    public static function expectedWidth(): int
    {
        return 228;
    }
}
