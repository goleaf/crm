<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Document;
use App\Models\DocumentShare;
use App\Models\DocumentTemplate;
use App\Models\DocumentVersion;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class DocumentSeeder extends Seeder
{
    public function run(): void
    {
        $team = Team::first() ?? Team::factory()->create();
        $owner = $team->owner ?? $team->user ?? User::first();

        if (! $owner) {
            $owner = User::factory()->create();
            $team->users()->attach($owner, ['role' => 'owner']);
        }

        // Ensure the team has a handful of members to share documents with.
        if ($team->users()->count() < 5) {
            $newUsers = User::factory()->count(5)->create();
            $team->users()->attach($newUsers->pluck('id'), ['role' => 'member']);
        }

        $users = $team->users()->get()->shuffle();

        $templates = DocumentTemplate::factory()
            ->count(4)
            ->create([
                'team_id' => $team->getKey(),
                'creator_id' => $owner->getKey(),
            ]);

        $visibilities = ['private', 'team', 'public'];

        Document::factory()
            ->count(18)
            ->state(fn (): array => [
                'team_id' => $team->getKey(),
                'creator_id' => $owner->getKey(),
                'template_id' => $templates->random()->getKey(),
                'visibility' => collect($visibilities)->random(),
                'description' => fake()->paragraph(),
            ])
            ->create()
            ->each(function (Document $document) use ($users): void {
                $versionCount = random_int(1, 3);

                for ($version = 1; $version <= $versionCount; $version++) {
                    $path = $this->generateFakePdf($document->title, $version);

                    DocumentVersion::create([
                        'document_id' => $document->getKey(),
                        'team_id' => $document->team_id,
                        'uploaded_by' => $document->creator_id,
                        'file_path' => $path,
                        'disk' => 'public',
                        'notes' => 'Auto-seeded version '.$version,
                    ]);
                }

                $this->seedShares($document, $users);
            });
    }

    private function generateFakePdf(string $title, int $version): string
    {
        $filename = Str::slug($title)."-v{$version}-".Str::random(6).'.pdf';
        $path = "documents/{$filename}";

        $content = "%PDF-1.4\n".
            "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n".
            "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n".
            "3 0 obj\n<< /Type /Page /Parent 2 0 R /Resources << >> /MediaBox [0 0 612 792] /Contents 4 0 R >>\nendobj\n".
            "4 0 obj\n<< /Length 44 >>\nstream\nBT /F1 24 Tf 100 700 Td ({$title} v{$version}) Tj ET\nendstream\nendobj\n".
            "xref\n0 5\n0000000000 65535 f \n0000000010 00000 n \n0000000060 00000 n \n0000000115 00000 n \n0000000250 00000 n \n".
            "trailer\n<< /Size 5 /Root 1 0 R >>\nstartxref\n350\n%%EOF";

        Storage::disk('public')->put($path, $content);

        return $path;
    }

    private function seedShares(Document $document, Collection $users): void
    {
        $shareCandidates = $users->shuffle()->take(random_int(3, 7));

        $shareCandidates->each(function (User $user) use ($document): void {
            DocumentShare::firstOrCreate(
                [
                    'document_id' => $document->getKey(),
                    'user_id' => $user->getKey(),
                ],
                [
                    'team_id' => $document->team_id,
                    'permission' => random_int(0, 1) !== 0 ? 'edit' : 'view',
                ]
            );
        });
    }
}
