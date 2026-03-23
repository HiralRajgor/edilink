<?php

declare(strict_types=1);

namespace Entelix\EdiLink\Console;

use Entelix\EdiLink\Facades\EdiLink;
use Illuminate\Console\Command;

class EdiGenerateCommand extends Command
{
    protected $signature = 'edilink:generate {carrier : Carrier code e.g. MSC} {--format=text}';
    protected $description = 'Generate an EDI file for a carrier. Override this command in your app to plug in your data source.';

    public function handle(): int
    {
        $carrier = strtoupper($this->argument('carrier'));
        $this->info("EDILink — registered carriers: " . implode(', ', EdiLink::carriers()));
        $this->info("Profile [{$carrier}] ready.");
        $this->comment("To generate EDI, call EdiLink::carrier('{$carrier}')->buildAll(\$records) from your own command or job.");
        $this->comment("See README for a full Laravel scheduler integration example.");
        return self::SUCCESS;
    }
}
