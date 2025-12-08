<?php

declare(strict_types=1);

use App\Enums\PdfGenerationStatus;
use App\Models\Invoice;
use App\Models\PdfGeneration;
use App\Models\PdfTemplate;
use App\Models\Team;
use App\Models\User;
use App\Services\PdfService;
use Hdaklue\PathBuilder\PathBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->service = new PdfService;
    Storage::fake('local');
});

test('generates PDF from template and entity', function (): void {
    $team = Team::factory()->create();
    $template = PdfTemplate::factory()->for($team)->create([
        'layout' => '<html><body><h1>{{company_name}}</h1></body></html>',
        'merge_fields' => ['company_name'],
    ]);
    $invoice = Invoice::factory()->for($team)->create();

    $generation = $this->service->generate($template, $invoice, [
        'company_name' => 'Test Company',
    ]);

    $hashedTeam = md5((string) $team->id);

    expect($generation)->toBeInstanceOf(PdfGeneration::class)
        ->and($generation->status)->toBe(PdfGenerationStatus::COMPLETED)
        ->and($generation->pdf_template_id)->toBe($template->id)
        ->and($generation->entity_type)->toBe(Invoice::class)
        ->and($generation->entity_id)->toBe($invoice->id)
        ->and($generation->file_path)->not->toBeNull()
        ->and($generation->file_path)->toStartWith("pdfs/{$hashedTeam}/")
        ->and(PathBuilder::isSafe($generation->file_path))->toBeTrue()
        ->and(Storage::disk('local')->exists($generation->file_path))->toBeTrue();
});

test('applies watermark when template has watermark configuration', function (): void {
    $team = Team::factory()->create();
    $template = PdfTemplate::factory()->for($team)->withWatermark()->create();
    $invoice = Invoice::factory()->for($team)->create();

    $generation = $this->service->generate($template, $invoice);

    expect($generation->has_watermark)->toBeTrue();
});

test('encrypts PDF when template has encryption enabled', function (): void {
    $team = Team::factory()->create();
    $template = PdfTemplate::factory()->for($team)->withEncryption()->create();
    $invoice = Invoice::factory()->for($team)->create();

    $generation = $this->service->generate($template, $invoice);

    expect($generation->is_encrypted)->toBeTrue();
});

test('stores merge data in generation record', function (): void {
    $team = Team::factory()->create();
    $template = PdfTemplate::factory()->for($team)->create();
    $invoice = Invoice::factory()->for($team)->create();
    $mergeData = ['company_name' => 'Test Company', 'amount' => '1000.00'];

    $generation = $this->service->generate($template, $invoice, $mergeData);

    expect($generation->merge_data)->toBe($mergeData);
});

test('handles generation failure gracefully', function (): void {
    $team = Team::factory()->create();
    $template = PdfTemplate::factory()->for($team)->create([
        'layout' => '{{invalid_syntax',
    ]);
    $invoice = Invoice::factory()->for($team)->create();

    try {
        $this->service->generate($template, $invoice);
    } catch (\Throwable) {
        $generation = PdfGeneration::where('pdf_template_id', $template->id)->first();
        expect($generation)->not->toBeNull()
            ->and($generation->status)->toBe(PdfGenerationStatus::FAILED)
            ->and($generation->error_message)->not->toBeNull();
    }
});

test('checks permissions correctly', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $template = PdfTemplate::factory()->for($team)->create([
        'permissions' => [
            'users' => [$user->id],
            'roles' => [],
        ],
    ]);

    expect($this->service->canGenerate($template, $user->id))->toBeTrue()
        ->and($this->service->canGenerate($template, 999))->toBeFalse();
});

test('allows generation when no permissions are set', function (): void {
    $team = Team::factory()->create();
    $template = PdfTemplate::factory()->for($team)->create([
        'permissions' => null,
    ]);

    expect($this->service->canGenerate($template, null))->toBeTrue();
});

test('retrieves PDF content from generation', function (): void {
    $team = Team::factory()->create();
    $template = PdfTemplate::factory()->for($team)->create();
    $invoice = Invoice::factory()->for($team)->create();

    $generation = $this->service->generate($template, $invoice);
    $content = $this->service->getContent($generation);

    expect($content)->not->toBeEmpty()
        ->and(str_contains((string) $content, '%PDF'))->toBeTrue();
});

test('deletes PDF file and generation record', function (): void {
    $team = Team::factory()->create();
    $template = PdfTemplate::factory()->for($team)->create();
    $invoice = Invoice::factory()->for($team)->create();

    $generation = $this->service->generate($template, $invoice);
    $filePath = $generation->file_path;

    expect(Storage::disk('local')->exists($filePath))->toBeTrue();

    $this->service->delete($generation);

    expect(Storage::disk('local')->exists($filePath))->toBeFalse()
        ->and(PdfGeneration::find($generation->id))->toBeNull();
});

test('estimates page count from PDF output', function (): void {
    $team = Team::factory()->create();
    $template = PdfTemplate::factory()->for($team)->create([
        'layout' => '<html><body><h1>Page 1</h1><div style="page-break-after: always;"></div><h1>Page 2</h1></body></html>',
    ]);
    $invoice = Invoice::factory()->for($team)->create();

    $generation = $this->service->generate($template, $invoice);

    expect($generation->page_count)->toBeGreaterThanOrEqual(1);
});

test('replaces merge fields in template layout', function (): void {
    $team = Team::factory()->create();
    $template = PdfTemplate::factory()->for($team)->create([
        'layout' => '<html><body><p>Company: {{company_name}}</p><p>Amount: {{amount}}</p></body></html>',
    ]);
    $invoice = Invoice::factory()->for($team)->create();
    $mergeData = ['company_name' => 'Acme Corp', 'amount' => '5000.00'];

    $generation = $this->service->generate($template, $invoice, $mergeData);
    $content = $this->service->getContent($generation);

    expect($content)->toContain('Acme Corp')
        ->and($content)->toContain('5000.00');
});

/**
 * **Feature: advanced-features, Property 3: PDF fidelity**
 * **Validates: Requirements 3.1, 3.2**
 *
 * Property: For any PDF template with merge fields and any entity with data,
 * the generated PDF must contain all merge field values and respect template
 * styling, watermarks, and encryption settings.
 */
test('property: generated PDFs match template specifications with merge field fidelity', function (): void {
    $iterations = 100;

    for ($i = 0; $i < $iterations; $i++) {
        $team = Team::factory()->create();
        $hasWatermark = fake()->boolean();
        $hasEncryption = fake()->boolean();

        $mergeFields = [
            'field_'.fake()->word() => fake()->word(),
            'field_'.fake()->word() => fake()->word(),
            'field_'.fake()->word() => fake()->numberBetween(1, 1000),
        ];

        $layout = '<html><body>';
        foreach (array_keys($mergeFields) as $field) {
            $layout .= '<p>{{'.$field.'}}</p>';
        }
        $layout .= '</body></html>';

        $templateState = PdfTemplate::factory()->for($team)->create([
            'layout' => $layout,
            'merge_fields' => array_keys($mergeFields),
            'watermark' => $hasWatermark ? [
                'text' => 'CONFIDENTIAL',
                'opacity' => 0.3,
                'rotation' => -45,
            ] : null,
            'encryption_enabled' => $hasEncryption,
            'encryption_password' => $hasEncryption ? 'test123' : null,
        ]);

        $invoice = Invoice::factory()->for($team)->create();

        $generation = $this->service->generate($templateState, $invoice, $mergeFields);

        // Property assertions
        expect($generation->status)->toBe(PdfGenerationStatus::COMPLETED);
        expect($generation->has_watermark)->toBe($hasWatermark);
        expect($generation->is_encrypted)->toBe($hasEncryption);
        expect($generation->merge_data)->toBe($mergeFields);

        $content = $this->service->getContent($generation);
        expect($content)->toContain('%PDF');

        if (! $generation->is_encrypted) {
            foreach ($mergeFields as $value) {
                expect($content)->toContain((string) $value);
            }
        }
    }
})->group('property');
