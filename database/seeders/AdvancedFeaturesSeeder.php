<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

final class AdvancedFeaturesSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            ProcessManagementSeeder::class,
            PdfTemplateSeeder::class,
            TerritorySeeder::class,
            EmailProgramSeeder::class,
        ]);
    }
}
