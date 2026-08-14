<?php

namespace Ahinest\LaravelAdvertising\Console;

use Ahinest\LaravelAdvertising\Database\Seeders\AdvertisingCategorySeeder;
use Illuminate\Console\Command;

/** Ejecuta el seeder de categorías publicitarias incluidas en el paquete. */
class SeedAdvertisingCategoriesCommand extends Command
{
    protected $signature = 'advertising:seed-categories';
    protected $description = 'Crea o actualiza las categorías IB, IE e IV de publicidad.';

    /** Ejecuta la siembra idempotente de categorías. */
    public function handle(): int
    {
        $this->call('db:seed', ['--class' => AdvertisingCategorySeeder::class]);
        return self::SUCCESS;
    }
}
