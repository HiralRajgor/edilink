<?php

declare(strict_types=1);

namespace Entelix\EdiLink\Tests\Integration\MSC;

use Entelix\EdiLink\DTOs\MovementRecord;
use Entelix\EdiLink\Profiles\MSC\MscCarrierProfile;
use PHPUnit\Framework\TestCase;

class MscCarrierProfileTest extends TestCase
{
    private MscCarrierProfile $profile;

    protected function setUp(): void
    {
        $this->profile = new MscCarrierProfile('text');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function makeRecord(array $overrides = []): MovementRecord
    {
        return MovementRecord::fromArray(array_merge([
            'id'                    => 'REC-001',
            'container_number'      => 'CMAU1234560',
            'iso_type'              => '20GP',
            'carrier_code'          => 'MSC',
            'reporting_party'       => 'ADEPOT',
            'depot_code'            => 'ADN01',
            'zone_id'               => 0,
            'arrived_at'            => '2024-06-01 08:00:00',
            'arrival_movement_type' => 'FULL_IN',
            'arrival_vehicle'       => 'GJ05TX1234',
            'delivery_order_ref'    => 'MSCUDO123456',
            'delivery_order_expiry' => '2024-06-10',
            'delivery_order_overdue'=> false,
            'dispatched_gate_in'    => '',
            'surveyed_at'           => '2024-06-01 10:00:00',
            'dispatched_survey'     => '',
            'sent_for_repair_at'    => '2024-06-02 09:00:00',
            'dispatched_mnr_in'     => '',
            'returned_from_repair_at' => '2024-06-05 14:00:00',
            'dispatched_mnr_out'    => '',
            'departed_at'           => '2024-06-08 11:00:00',
            'departure_movement_type' => 'FULL_OUT',
            'departure_vehicle'     => 'MH04CD5678',
            'booking_ref'           => 'MSCUBOOK001',
            'booking_expiry'        => '2024-06-15',
            'booking_overdue'       => false,
            'consignee_name'        => 'ACME EXPORTS LTD',
            'seal_reference'        => 'MSC987654',
            'dispatched_gate_out'   => '',
        ], $overrides));
    }

    // ── Gate In ───────────────────────────────────────────────────────────────

    public function test_gate_in_produces_output_for_pending_record(): void
    {
        $output = $this->profile->buildGateIn([$this->makeRecord()]);
        $this->assertTrue($output->hasContent());
        $this->assertCount(1, $output->includedIds);
        $this->assertEquals('gate_in', $output->eventType);
    }

    public function test_gate_in_skips_already_dispatched(): void
    {
        $record = $this->makeRecord(['dispatched_gate_in' => 'MSC_EDI_01JUN2024.txt']);
        $output = $this->profile->buildGateIn([$record]);
        $this->assertFalse($output->hasContent());
        $this->assertCount(0, $output->includedIds);
    }

    public function test_gate_in_skips_dock_destuff(): void
    {
        $record = $this->makeRecord(['arrival_movement_type' => 'DOCK_DESTUFF']);
        $output = $this->profile->buildGateIn([$record]);
        $this->assertFalse($output->hasContent());
    }

    public function test_gate_in_applies_zone_override_for_mundra(): void
    {
        $record = $this->makeRecord(['zone_id' => 26, 'arrival_movement_type' => 'FULL_IN']);
        $output = $this->profile->buildGateIn([$record]);
        // Zone 26 Mundra: FULL_IN → MCY
        $this->assertStringContainsString('MCY', $output->content);
    }

    public function test_gate_in_uses_do_expiry_when_overdue(): void
    {
        $record = $this->makeRecord([
            'delivery_order_overdue' => true,
            'delivery_order_expiry'  => '2024-05-30',
            'arrived_at'             => '2024-06-01 08:00:00',
        ]);
        $output = $this->profile->buildGateIn([$record]);
        // Effective date becomes 30052024
        $this->assertStringContainsString('30052024', $output->content);
    }

    public function test_gate_in_line_is_correct_width(): void
    {
        $output = $this->profile->buildGateIn([$this->makeRecord()]);
        $lines  = array_filter(explode("\n", $output->content));

        foreach ($lines as $line) {
            $this->assertEquals(228, strlen(rtrim($line, "\r")));
        }
    }

    // ── Survey ────────────────────────────────────────────────────────────────

    public function test_survey_is_generated_with_dam_code(): void
    {
        $output = $this->profile->buildSurvey([$this->makeRecord()]);
        $this->assertStringContainsString('DAM', $output->content);
    }

    public function test_survey_skipped_when_arrival_after_survey(): void
    {
        $record = $this->makeRecord([
            'arrived_at'  => '2024-06-01 11:00:00',
            'surveyed_at' => '2024-06-01 10:00:00', // before arrival — invalid
        ]);
        $output = $this->profile->buildSurvey([$record]);
        $this->assertFalse($output->hasContent());
    }

    // ── Repair Dispatch ───────────────────────────────────────────────────────

    public function test_repair_dispatch_emits_tbr_code(): void
    {
        $output = $this->profile->buildRepairDispatch([$this->makeRecord()]);
        $this->assertStringContainsString('TBR', $output->content);
    }

    public function test_repair_dispatch_requires_survey_before_dispatch(): void
    {
        $record = $this->makeRecord([
            'surveyed_at'        => '2024-06-02 10:00:00',
            'sent_for_repair_at' => '2024-06-02 09:00:00', // before survey — invalid
        ]);
        $output = $this->profile->buildRepairDispatch([$record]);
        $this->assertFalse($output->hasContent());
    }

    // ── Repair Return ─────────────────────────────────────────────────────────

    public function test_repair_return_emits_rep_code(): void
    {
        $output = $this->profile->buildRepairReturn([$this->makeRecord()]);
        $this->assertStringContainsString('REP', $output->content);
    }

    // ── Gate Out ──────────────────────────────────────────────────────────────

    public function test_gate_out_requires_full_chain(): void
    {
        $output = $this->profile->buildGateOut([$this->makeRecord()]);
        $this->assertTrue($output->hasContent());
        $this->assertStringContainsString('MSCUBOOK001', $output->content);
    }

    public function test_gate_out_skipped_with_broken_chain(): void
    {
        // Repair return AFTER departure — invalid
        $record = $this->makeRecord([
            'returned_from_repair_at' => '2024-06-09 08:00:00', // after gate_out
            'departed_at'             => '2024-06-08 11:00:00',
        ]);
        $output = $this->profile->buildGateOut([$record]);
        $this->assertFalse($output->hasContent());
    }

    public function test_gate_out_uses_booking_expiry_when_overdue(): void
    {
        $record = $this->makeRecord([
            'booking_overdue' => true,
            'booking_expiry'  => '2024-06-07',
        ]);
        $output = $this->profile->buildGateOut([$record]);
        $this->assertStringContainsString('07062024', $output->content);
    }

    // ── Build All ─────────────────────────────────────────────────────────────

    public function test_build_all_returns_all_event_types(): void
    {
        $results = $this->profile->buildAll([$this->makeRecord()]);

        $this->assertArrayHasKey('gate_in', $results);
        $this->assertArrayHasKey('survey', $results);
        $this->assertArrayHasKey('repair_dispatch', $results);
        $this->assertArrayHasKey('repair_return', $results);
        $this->assertArrayHasKey('gate_out', $results);
    }

    public function test_build_all_full_lifecycle_all_have_content(): void
    {
        $results = $this->profile->buildAll([$this->makeRecord()]);

        $this->assertTrue($results['gate_in']->hasContent());
        $this->assertTrue($results['survey']->hasContent());
        $this->assertTrue($results['repair_dispatch']->hasContent());
        $this->assertTrue($results['repair_return']->hasContent());
        $this->assertTrue($results['gate_out']->hasContent());
    }

    // ── Array format ──────────────────────────────────────────────────────────

    public function test_array_format_returns_json_decodable_rows(): void
    {
        $profile = new MscCarrierProfile('array');
        $output  = $profile->buildGateIn([$this->makeRecord()]);

        $rows = json_decode($output->content, true);
        $this->assertIsArray($rows);
        $this->assertCount(1, $rows);
        $this->assertArrayHasKey('container_number', $rows[0]);
        $this->assertEquals('CMAU1234560', $rows[0]['container_number']);
    }

    // ── MovementRecord builder ────────────────────────────────────────────────

    public function test_movement_record_from_array_maps_keys(): void
    {
        $record = MovementRecord::fromArray([
            'container_number' => 'MSCU9999990',
            'carrier_code'     => 'MSC',
            'depot_code'       => 'TDP',
            'zone_id'          => 26,
        ]);

        $this->assertEquals('MSCU9999990', $record->containerNumber);
        $this->assertEquals('MSC', $record->carrierCode);
        $this->assertEquals('TDP', $record->depotCode);
        $this->assertEquals(26, $record->zoneId);
    }

    public function test_movement_record_fluent_builder(): void
    {
        $record = MovementRecord::build()
            ->identity('CMAU0000001', '40HC', 'MSC')
            ->depot('HZR', zone: 15)
            ->arrival('2024-06-01 07:00:00', movementType: 'FULL_IN')
            ->ediFlags(gateIn: '')
            ->id('789')
            ->make();

        $this->assertEquals('CMAU0000001', $record->containerNumber);
        $this->assertEquals(15, $record->zoneId);
        $this->assertEquals('789', $record->recordId);
    }
}
