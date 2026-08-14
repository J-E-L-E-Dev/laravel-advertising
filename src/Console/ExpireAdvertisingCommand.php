<?php

namespace Ahinest\LaravelAdvertising\Console;

use Ahinest\LaravelAdvertising\Services\AdvertisingManager;
use Illuminate\Console\Command;

class ExpireAdvertisingCommand extends Command
{
    protected $signature = 'advertising:expire';
    protected $description = 'Soft-delete expired advertising resources, containers and advertisements.';

    public function handle(AdvertisingManager $manager): int
    {
        $counts = $manager->expire();
        $this->info('Expired records deleted: '.array_sum($counts));
        return self::SUCCESS;
    }
}
