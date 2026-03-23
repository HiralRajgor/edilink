<?php

declare(strict_types=1);

namespace Entelix\EdiLink\Tests\Unit;

use Entelix\EdiLink\Core\EdiLine;
use Entelix\EdiLink\Core\EdiOutput;
use Entelix\EdiLink\Core\EdiSegment;
use PHPUnit\Framework\TestCase;

class CoreTest extends TestCase
{
    public function test_segment_pads_short_value(): void
    {
        $seg = new EdiSegment('carrier', 5, 'MSC');
        $this->assertEquals('MSC  ', $seg->render());
    }

    public function test_segment_truncates_long_value(): void
    {
        $seg = new EdiSegment('carrier', 5, 'TOOLONG');
        $this->assertEquals('TOOLO', $seg->render());
    }

    public function test_segment_handles_empty_value(): void
    {
        $seg = new EdiSegment('field', 10, '');
        $this->assertEquals('          ', $seg->render());
        $this->assertEquals(10, strlen($seg->render()));
    }

    public function test_edi_line_produces_correct_total_width(): void
    {
        $line = EdiLine::make()
            ->add('carrier',   5,  'MSC')
            ->add('container', 15, 'CMAU1234560')
            ->add('iso_type',  10, '20GP');

        $text = $line->toText();
        // 5 + 15 + 10 = 30 + \r\n = 32
        $this->assertEquals(32, strlen($text));
    }

    public function test_edi_line_to_array_preserves_field_names(): void
    {
        $line = EdiLine::make()
            ->add('carrier',   5,  'MSC')
            ->add('container', 15, 'CMAU1234560');

        $arr = $line->toArray();
        $this->assertArrayHasKey('carrier', $arr);
        $this->assertArrayHasKey('container', $arr);
        $this->assertEquals('MSC', $arr['carrier']);
        $this->assertEquals('CMAU1234560', $arr['container']);
    }

    public function test_edi_line_total_width(): void
    {
        $line = EdiLine::make()
            ->add('f1', 5,  '')
            ->add('f2', 15, '')
            ->add('f3', 10, '');

        $this->assertEquals(30, $line->totalWidth());
    }

    public function test_edi_output_has_content_when_not_empty(): void
    {
        $output = new EdiOutput("MSC  CMAU1234560\r\n", ['101'], 'gate_in');
        $this->assertTrue($output->hasContent());
        $this->assertEquals(1, $output->lineCount());
        $this->assertEquals(1, $output->recordCount());
    }

    public function test_edi_output_empty_factory(): void
    {
        $output = EdiOutput::empty('stuffing');
        $this->assertFalse($output->hasContent());
        $this->assertEquals('stuffing', $output->eventType);
        $this->assertCount(0, $output->includedIds);
    }

    public function test_edi_output_merge(): void
    {
        $a = new EdiOutput("LINE1\r\n", ['1'], 'gate_in');
        $b = new EdiOutput("LINE2\r\n", ['2'], 'gate_in');

        $merged = EdiOutput::merge([$a, $b], 'gate_in');
        $this->assertEquals("LINE1\r\nLINE2\r\n", $merged->content);
        $this->assertCount(2, $merged->includedIds);
    }
}
