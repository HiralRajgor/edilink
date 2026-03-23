<?php

declare(strict_types=1);

namespace Entelix\EdiLink\Core;

/**
 * EdiLine
 *
 * An ordered collection of EdiSegments that renders to a single EDI line.
 * Handles both fixed-width text output (for file-based EDI) and
 * associative array output (for Excel / OVA / API responses).
 *
 * Usage:
 *   $line = EdiLine::make()
 *       ->add('carrier',      5,  'MSC')
 *       ->add('container',    15, 'CMAU1234560')
 *       ->add('iso_type',     10, '20GP')
 *       ->add('event_code',   10, 'FULL_IN')
 *       ->add('timestamp',    13, '150620240830');
 *
 *   $line->toText();  // fixed-width string + CRLF
 *   $line->toArray(); // ['carrier' => 'MSC', 'container' => 'CMAU1234560', ...]
 */
final class EdiLine
{
    /** @var EdiSegment[] */
    private array $segments = [];

    public static function make(): self
    {
        return new self();
    }

    /**
     * Append a new segment to this line.
     */
    public function add(string $name, int $width, string $value = ''): self
    {
        $this->segments[] = new EdiSegment($name, $width, $value);
        return $this;
    }

    /**
     * Set the value of an existing segment by name.
     * Use this when building from a schema (MscLineSchema::blank()) so you
     * update values in-place rather than appending duplicate segments.
     *
     * @throws \InvalidArgumentException if the segment name does not exist
     */
    public function set(string $name, string $value): self
    {
        foreach ($this->segments as $i => $segment) {
            if ($segment->name === $name) {
                $this->segments[$i] = $segment->withValue($value);
                return $this;
            }
        }
        throw new \InvalidArgumentException(
            "EdiLine: no segment named [{$name}]. "
            . "Available: " . implode(', ', array_map(fn($s) => $s->name, $this->segments))
        );
    }

    /**
     * Render as a fixed-width text line with CRLF terminator.
     * This is the standard format for EDI files sent to shipping lines.
     */
    public function toText(): string
    {
        return implode('', array_map(
            fn(EdiSegment $s) => $s->render(),
            $this->segments
        )) . "\r\n";
    }

    /**
     * Render as an associative array of [field_name => value].
     * Used for Excel/OVA export and API JSON responses.
     */
    public function toArray(): array
    {
        $result = [];
        foreach ($this->segments as $segment) {
            $result[$segment->name] = $segment->value;
        }
        return $result;
    }

    /**
     * Total character width of all segments (excluding line terminator).
     */
    public function totalWidth(): int
    {
        return array_sum(array_map(fn(EdiSegment $s) => $s->width, $this->segments));
    }

    /**
     * Number of segments in this line.
     */
    public function segmentCount(): int
    {
        return count($this->segments);
    }
}
