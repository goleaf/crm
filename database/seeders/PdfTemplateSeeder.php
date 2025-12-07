<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\PdfGeneration;
use App\Models\PdfTemplate;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class PdfTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Creating PDF templates with generations...');

        $teams = Team::all();
        $users = User::all();

        if ($teams->isEmpty() || $users->isEmpty()) {
            $this->command->warn('No teams or users found.');

            return;
        }

        foreach ($teams as $team) {
            $creator = $users->random();

            $templates = PdfTemplate::factory()
                ->count(3)
                ->create([
                    'team_id' => $team->id,
                    'creator_id' => $creator->id,
                    'entity_type' => \App\Models\Company::class,
                    'merge_fields' => ['company_name', 'amount', 'date'],
                    'layout' => '<h1>{{ company_name }}</h1><p>Amount: {{ amount }}</p>',
                    'key' => fn (): string => 'tpl-'.Str::random(8),
                ]);

            foreach ($templates as $template) {
                for ($i = 1; $i <= 3; $i++) {
                    $path = $this->fakePdf("{$template->key}-{$i}");

                    PdfGeneration::create([
                        'team_id' => $team->id,
                        'pdf_template_id' => $template->id,
                        'user_id' => $creator->id,
                        'entity_type' => \App\Models\Company::class,
                        'entity_id' => 1,
                        'file_path' => $path,
                        'file_name' => basename($path),
                        'file_size' => Storage::disk('public')->size($path),
                        'page_count' => 1,
                        'merge_data' => ['company_name' => fake()->company(), 'amount' => fake()->randomFloat(2, 1000, 50000)],
                        'generation_options' => ['watermark' => false],
                        'has_watermark' => false,
                        'is_encrypted' => false,
                        'status' => 'completed',
                        'generated_at' => now()->subDays($i),
                    ]);
                }
            }
        }

        $this->command->info('✓ Created PDF templates with sample generations');
    }

    private function fakePdf(string $name): string
    {
        $filename = "{$name}.pdf";
        $path = "pdfs/{$filename}";

        $content = "%PDF-1.4\n1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n".
            "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n".
            "3 0 obj\n<< /Type /Page /Parent 2 0 R /Resources << >> /MediaBox [0 0 612 792] /Contents 4 0 R >>\nendobj\n".
            "4 0 obj\n<< /Length 44 >>\nstream\nBT /F1 24 Tf 100 700 Td ({$name}) Tj ET\nendstream\nendobj\n".
            "xref\n0 5\n0000000000 65535 f \n0000000010 00000 n \n0000000060 00000 n \n0000000115 00000 n \n0000000250 00000 n \n".
            "trailer\n<< /Size 5 /Root 1 0 R >>\nstartxref\n350\n%%EOF";

        Storage::disk('public')->put($path, $content);

        return $path;
    }
}
