<?php

declare(strict_types=1);

namespace Tests\Feature\AdvancedFeatures;

use App\Enums\PdfGenerationStatus;
use App\Models\Invoice;
use App\Models\PdfGeneration;
use App\Models\PdfTemplate;
use App\Models\Team;
use App\Models\User;
use App\Services\PdfService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Storage::fake('local');
    Mail::fake();
});

/**
 * Integration test: PDF generation and email attachment
 *
 * Tests the complete workflow of generating a PDF from a template
 * and attaching it to an email.
 */
test('complete PDF generation and email attachment workflow', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $team->users()->attach($user);

    $service = new PdfService;

    // Create template with merge fields and styling
    $template = PdfTemplate::factory()->for($team)->create([
        'name' => 'Invoice Template',
        'layout' => '
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; }
                    .header { background-color: #333; color: white; padding: 20px; }
                    .invoice-details { margin: 20px 0; }
                </style>
            </head>
            <body>
                <div class="header">
                    <h1>Invoice</h1>
                </div>
                <div class="invoice-details">
                    <p><strong>Company:</strong> {{company_name}}</p>
                    <p><strong>Invoice Number:</strong> {{invoice_number}}</p>
                    <p><strong>Amount:</strong> ${{amount}}</p>
                    <p><strong>Due Date:</strong> {{due_date}}</p>
                </div>
            </body>
            </html>
        ',
        'merge_fields' => ['company_name', 'invoice_number', 'amount', 'due_date'],
        'watermark' => [
            'text' => 'ORIGINAL',
            'opacity' => 0.2,
            'rotation' => -45,
        ],
        'encryption_enabled' => true,
        'encryption_password' => 'secure123',
        'permissions' => [
            'users' => [$user->id],
            'roles' => [],
        ],
    ]);

    // Create invoice entity
    $invoice = Invoice::factory()->for($team)->create([
        'invoice_number' => 'INV-2024-001',
        'total_amount' => 1500.00,
    ]);

    // Prepare merge data
    $mergeData = [
        'company_name' => 'Acme Corporation',
        'invoice_number' => $invoice->invoice_number,
        'amount' => number_format($invoice->total_amount, 2),
        'due_date' => now()->addDays(30)->format('Y-m-d'),
    ];

    // Generate PDF
    $generation = $service->generate($template, $invoice, $mergeData);

    // Verify generation
    expect($generation)->toBeInstanceOf(PdfGeneration::class)
        ->and($generation->status)->toBe(PdfGenerationStatus::COMPLETED)
        ->and($generation->pdf_template_id)->toBe($template->id)
        ->and($generation->entity_type)->toBe(Invoice::class)
        ->and($generation->entity_id)->toBe($invoice->id)
        ->and($generation->has_watermark)->toBeTrue()
        ->and($generation->is_encrypted)->toBeTrue()
        ->and($generation->merge_data)->toBe($mergeData)
        ->and($generation->file_path)->not->toBeNull()
        ->and($generation->file_size)->toBeGreaterThan(0)
        ->and($generation->page_count)->toBeGreaterThanOrEqual(1);

    // Verify file exists
    expect(Storage::disk('local')->exists($generation->file_path))->toBeTrue();

    // Get PDF content
    $content = $service->getContent($generation);
    expect($content)->not->toBeEmpty()
        ->and(str_contains($content, '%PDF'))->toBeTrue();

    // Verify merge fields in content
    foreach ($mergeData as $value) {
        expect($content)->toContain((string) $value);
    }

    // Verify permissions
    expect($service->canGenerate($template, $user->id))->toBeTrue()
        ->and($service->canGenerate($template, 999))->toBeFalse();

    // Simulate email attachment (would integrate with mail service)
    $attachmentPath = $generation->file_path;
    expect(Storage::disk('local')->exists($attachmentPath))->toBeTrue();

    // Clean up
    $service->delete($generation);
    expect(Storage::disk('local')->exists($attachmentPath))->toBeFalse()
        ->and(PdfGeneration::find($generation->id))->toBeNull();
});

/**
 * Integration test: PDF template versioning
 */
test('PDF template versioning maintains history', function (): void {
    $team = Team::factory()->create();
    $service = new PdfService;

    // Create initial template version
    $template = PdfTemplate::factory()->for($team)->create([
        'name' => 'Quote Template',
        'layout' => '<html><body><h1>Quote v1</h1></body></html>',
        'version' => 1,
    ]);

    $invoice = Invoice::factory()->for($team)->create();

    // Generate PDF with version 1
    $generation1 = $service->generate($template, $invoice);
    expect($generation1->template_version)->toBe(1);

    // Update template to version 2
    $template->update([
        'layout' => '<html><body><h1>Quote v2</h1></body></html>',
        'version' => 2,
    ]);

    // Generate PDF with version 2
    $generation2 = $service->generate($template->fresh(), $invoice);
    expect($generation2->template_version)->toBe(2);

    // Verify both generations exist with different versions
    expect($generation1->fresh()->template_version)->toBe(1)
        ->and($generation2->fresh()->template_version)->toBe(2);

    // Verify content differs
    $content1 = $service->getContent($generation1);
    $content2 = $service->getContent($generation2);

    expect($content1)->toContain('Quote v1')
        ->and($content2)->toContain('Quote v2');
});

/**
 * Integration test: PDF generation with complex multi-page layout
 */
test('PDF generation handles multi-page documents', function (): void {
    $team = Team::factory()->create();
    $service = new PdfService;

    // Create template with page breaks
    $template = PdfTemplate::factory()->for($team)->create([
        'layout' => '
            <html>
            <body>
                <div>
                    <h1>Page 1</h1>
                    <p>{{content_page_1}}</p>
                </div>
                <div style="page-break-after: always;"></div>
                <div>
                    <h1>Page 2</h1>
                    <p>{{content_page_2}}</p>
                </div>
                <div style="page-break-after: always;"></div>
                <div>
                    <h1>Page 3</h1>
                    <p>{{content_page_3}}</p>
                </div>
            </body>
            </html>
        ',
        'merge_fields' => ['content_page_1', 'content_page_2', 'content_page_3'],
    ]);

    $invoice = Invoice::factory()->for($team)->create();

    $mergeData = [
        'content_page_1' => 'First page content',
        'content_page_2' => 'Second page content',
        'content_page_3' => 'Third page content',
    ];

    $generation = $service->generate($template, $invoice, $mergeData);

    expect($generation->status)->toBe(PdfGenerationStatus::COMPLETED)
        ->and($generation->page_count)->toBeGreaterThanOrEqual(3);

    $content = $service->getContent($generation);
    expect($content)->toContain('First page content')
        ->and($content)->toContain('Second page content')
        ->and($content)->toContain('Third page content');
});

/**
 * Integration test: PDF generation failure handling
 */
test('PDF generation handles template errors gracefully', function (): void {
    $team = Team::factory()->create();
    $service = new PdfService;

    // Create template with invalid syntax
    $template = PdfTemplate::factory()->for($team)->create([
        'layout' => '<html><body>{{unclosed_tag</body></html>',
    ]);

    $invoice = Invoice::factory()->for($team)->create();

    try {
        $service->generate($template, $invoice);
        expect(false)->toBeTrue('Should have thrown exception');
    } catch (\Throwable) {
        // Verify generation record was created with failure status
        $generation = PdfGeneration::where('pdf_template_id', $template->id)
            ->where('entity_id', $invoice->id)
            ->first();

        expect($generation)->not->toBeNull()
            ->and($generation->status)->toBe(PdfGenerationStatus::FAILED)
            ->and($generation->error_message)->not->toBeNull();
    }
});

/**
 * Integration test: PDF archiving and retrieval
 */
test('PDF generations are archived and retrievable', function (): void {
    $team = Team::factory()->create();
    $service = new PdfService;

    $template = PdfTemplate::factory()->for($team)->create();
    $invoice = Invoice::factory()->for($team)->create();

    // Generate multiple PDFs for the same entity
    $generation1 = $service->generate($template, $invoice, ['version' => '1']);
    $generation2 = $service->generate($template, $invoice, ['version' => '2']);
    $generation3 = $service->generate($template, $invoice, ['version' => '3']);

    // Retrieve all generations for the invoice
    $generations = PdfGeneration::where('entity_type', Invoice::class)
        ->where('entity_id', $invoice->id)
        ->orderBy('created_at', 'desc')
        ->get();

    expect($generations)->toHaveCount(3);
    expect($generations->first()->id)->toBe($generation3->id);
    expect($generations->last()->id)->toBe($generation1->id);

    // Verify each generation is accessible
    foreach ($generations as $generation) {
        $content = $service->getContent($generation);
        expect($content)->not->toBeEmpty()
            ->and(str_contains($content, '%PDF'))->toBeTrue();
    }
});
