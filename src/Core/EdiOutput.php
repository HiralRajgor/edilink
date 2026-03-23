<?php

declare(strict_types=1);

namespace Entelix\EdiLink\Core;

use DateTimeImmutable;

/**
 * EdiOutput
 *
 * Immutable value object returned by every carrier profile method.
 *
 * Contains the generated EDI content plus metadata about what was written,
 * so the calling application can update its own database without EDILink
 * needing any DB access of its own.
 *
 * Example:
 *   $output = $carrier->buildGateIn($records);
 *
 *   file_put_contents($path, $output->content);
 *
 *   // Update your DB for the records that were included
 *   YourModel::whereIn('id', $output->includedIds)
 *             ->update(['edi_gate_in' => $filename]);
 *
 *   // Access structured rows for Excel export
 *   foreach ($output->rows as $row) { ... }
 */
final class EdiOutput
{
    /**
     * @param string   $content      The complete EDI text content for this event type.
     * @param string[] $includedIds  Record IDs (your PK) that were written into this output.
     * @param string   $eventType    Machine-readable event slug: 'gate_in', 'survey', etc.
     * @param array[]  $rows         Structured rows for Excel/OVA/API use.
     */
    public function __construct(
        public readonly string            $content,
        public readonly array             $includedIds,
        public readonly string            $eventType,
        public readonly array             $rows           = [],
        public readonly ?DateTimeImmutable $generatedAt   = null,
    ) {}

    /**
     * True when this output contains no EDI lines.
     */
    public function hasContent(): bool
    {
        return trim($this->content) !== '';
    }

    /**
     * Number of EDI lines in this output.
     */
    public function lineCount(): int
    {
        if (! $this->hasContent()) return 0;
        return substr_count(rtrim($this->content), "\n") + 1;
    }

    /**
     * Number of unique records included.
     */
    public function recordCount(): int
    {
        return count($this->includedIds);
    }

    /**
     * Merge multiple EdiOutput objects of the same event type into one.
     *
     * @param EdiOutput[] $outputs
     */
    public static function merge(array $outputs, string $eventType = 'merged'): self
    {
        $content     = '';
        $includedIds = [];
        $rows        = [];

        foreach ($outputs as $output) {
            $content     .= $output->content;
            $includedIds  = array_merge($includedIds, $output->includedIds);
            $rows         = array_merge($rows, $output->rows);
        }

        return new self($content, array_unique($includedIds), $eventType, $rows, new DateTimeImmutable());
    }

    /**
     * Return an empty output for a given event type.
     * Used when no records are eligible for a specific event.
     */
    public static function empty(string $eventType): self
    {
        return new self('', [], $eventType, [], new DateTimeImmutable());
    }
}
