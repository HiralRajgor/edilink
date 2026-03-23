<?php

declare(strict_types=1);

namespace Entelix\EdiLink\Core;

/**
 * EdiSegment
 *
 * Represents one field within an EDI line.
 * Carries a width constraint and a value; serialises to fixed-width padded string.
 *
 * This is the fundamental building block of EDILink's generation pipeline.
 * Every carrier profile builds lines from EdiSegments rather than raw string
 * concatenation, ensuring field widths are always respected.
 */
final class EdiSegment
{
    public function __construct(
        public readonly string $name,
        public readonly int    $width,
        public readonly string $value = '',
    ) {}

    /**
     * Render this segment as a fixed-width padded string.
     * Values longer than $width are truncated; shorter values are right-padded.
     */
    public function render(): string
    {
        return str_pad(
            mb_substr($this->value, 0, $this->width),
            $this->width,
            ' ',
            STR_PAD_RIGHT
        );
    }

    public function withValue(string $value): self
    {
        return new self($this->name, $this->width, $value);
    }

    public function __toString(): string
    {
        return $this->render();
    }
}
