<?php

declare(strict_types=1);

namespace Entelix\EdiLink\Console;

use Entelix\EdiLink\Profiles\MSC\MscLineSchema;
use Illuminate\Console\Command;

class EdiValidateCommand extends Command
{
    protected $signature   = 'edilink:validate {file} {--carrier=MSC}';
    protected $description = 'Validate an EDI file against a carrier schema';

    public function handle(): int
    {
        $path    = $this->argument('file');
        $carrier = strtoupper($this->option('carrier'));

        if (! file_exists($path)) {
            $this->error("File not found: {$path}");
            return self::FAILURE;
        }

        $expectedWidth = match ($carrier) {
            'MSC'   => MscLineSchema::expectedWidth(),
            default => null,
        };

        $lines    = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $errors   = [];
        $warnings = [];

        foreach ($lines as $i => $line) {
            $lineNo = $i + 1;
            $len    = strlen(rtrim($line, "\r"));

            if ($expectedWidth && $len !== $expectedWidth) {
                $errors[] = "Line {$lineNo}: width {$len}, expected {$expectedWidth}";
            }

            // Container number at offset 5, width 15
            $containerNo = trim(substr($line, 5, 15));
            if (empty($containerNo)) {
                $errors[] = "Line {$lineNo}: container number is empty";
            }

            // Event code at offset 30, width 10
            $eventCode = trim(substr($line, 30, 10));
            if (empty($eventCode)) {
                $warnings[] = "Line {$lineNo}: event code is empty";
            }
        }

        $this->info(sprintf('Validated %s — %d lines', basename($path), count($lines)));

        if ($errors) {
            $this->error(count($errors) . ' error(s):');
            foreach ($errors as $e) $this->line("  ✗ {$e}");
        }
        if ($warnings) {
            $this->warn(count($warnings) . ' warning(s):');
            foreach ($warnings as $w) $this->line("  ⚠ {$w}");
        }
        if (! $errors && ! $warnings) {
            $this->info('✓ No issues found.');
        }

        return $errors ? self::FAILURE : self::SUCCESS;
    }
}
