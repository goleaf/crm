<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * **Feature: accounts-module, Property 34: Document attachment lifecycle**
 *
 * **Validates: Requirements 15.1, 15.2, 15.5**
 *
 * Properties:
 * - For any document uploaded to an account, the system should store it with metadata
 *   (filename, size, upload date, uploader), make it retrievable, and allow deletion
 *   with appropriate permissions.
 */

// Property 34: Document attachment lifecycle - upload and metadata storage
test('property: document upload stores file with complete metadata', function (): void {
    Storage::fake('public');

    $team = Team::factory()->create();
    $owner = User::factory()->create();
    $team->users()->attach($owner);

    $company = Company::factory()
        ->for($team)
        ->create(['account_owner_id' => $owner->getKey()]);

    // Generate random file data
    $filename = fake()->word() . '.' . fake()->randomElement(['pdf', 'docx', 'xlsx', 'pptx', 'jpg', 'png']);
    $fileSize = fake()->numberBetween(1024, 10485760); // 1KB to 10MB
    $mimeType = match (pathinfo($filename, PATHINFO_EXTENSION)) {
        'pdf' => 'application/pdf',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'jpg' => 'image/jpeg',
        'png' => 'image/png',
        default => 'application/octet-stream',
    };

    $file = UploadedFile::fake()->create($filename, $fileSize / 1024, $mimeType);

    // Upload the file
    $media = $company->addMediaFromRequest('file')
        ->usingFileName($filename)
        ->withCustomProperties([
            'uploaded_by' => $owner->getKey(),
            'original_filename' => $filename,
        ])
        ->toMediaCollection('attachments');

    // Verify file was stored
    expect($media)->toBeInstanceOf(Media::class);
    expect($media->collection_name)->toBe('attachments');
    expect($media->name)->toBe(pathinfo($filename, PATHINFO_FILENAME));
    expect($media->file_name)->toBe($filename);
    expect($media->mime_type)->toBe($mimeType);
    expect($media->size)->toBeGreaterThan(0);
    expect($media->created_at)->not->toBeNull();

    // Verify custom properties
    expect($media->getCustomProperty('uploaded_by'))->toBe($owner->getKey());
    expect($media->getCustomProperty('original_filename'))->toBe($filename);

    // Verify file is retrievable through company relationship
    $attachments = $company->attachments;
    expect($attachments)->toHaveCount(1);
    expect($attachments->first()->id)->toBe($media->id);

    // Verify file exists in storage
    Storage::disk('public')->assertExists($media->getPath());
})->repeat(100);

// Property 34: Document attachment lifecycle - multiple files support
test('property: multiple documents can be attached to same account', function (): void {
    Storage::fake('public');

    $team = Team::factory()->create();
    $owner = User::factory()->create();
    $team->users()->attach($owner);

    $company = Company::factory()
        ->for($team)
        ->create(['account_owner_id' => $owner->getKey()]);

    // Upload multiple files
    $fileCount = fake()->numberBetween(2, 5);
    $uploadedFiles = [];

    for ($i = 0; $i < $fileCount; $i++) {
        $filename = fake()->word() . '_' . $i . '.' . fake()->randomElement(['pdf', 'docx', 'jpg']);
        $file = UploadedFile::fake()->create($filename, fake()->numberBetween(100, 1000));

        $media = $company->addMediaFromRequest('file')
            ->usingFileName($filename)
            ->withCustomProperties(['uploaded_by' => $owner->getKey()])
            ->toMediaCollection('attachments');

        $uploadedFiles[] = $media;
    }

    // Verify all files are attached
    $attachments = $company->attachments;
    expect($attachments)->toHaveCount($fileCount);

    // Verify each file is properly stored
    foreach ($uploadedFiles as $media) {
        expect($attachments->pluck('id'))->toContain($media->id);
        Storage::disk('public')->assertExists($media->getPath());
    }

    // Verify files are ordered by creation date (latest first)
    $attachmentIds = $attachments->pluck('id')->toArray();
    $expectedOrder = collect($uploadedFiles)->sortByDesc('created_at')->pluck('id')->toArray();
    expect($attachmentIds)->toBe($expectedOrder);
})->repeat(50);

// Property 34: Document attachment lifecycle - file retrieval
test('property: attached documents are retrievable with complete metadata', function (): void {
    Storage::fake('public');

    $team = Team::factory()->create();
    $owner = User::factory()->create();
    $team->users()->attach($owner);

    $company = Company::factory()
        ->for($team)
        ->create(['account_owner_id' => $owner->getKey()]);

    $filename = fake()->word() . '.pdf';
    $fileContent = fake()->text(1000);
    $file = UploadedFile::fake()->createWithContent($filename, $fileContent);

    // Upload file with metadata
    $uploadTime = now();
    $media = $company->addMediaFromRequest('file')
        ->usingFileName($filename)
        ->withCustomProperties([
            'uploaded_by' => $owner->getKey(),
            'description' => fake()->sentence(),
            'category' => fake()->randomElement(['contract', 'proposal', 'invoice']),
        ])
        ->toMediaCollection('attachments');

    // Retrieve file through different methods
    $retrievedById = Media::find($media->id);
    $retrievedByCompany = $company->attachments()->where('id', $media->id)->first();
    $retrievedByCollection = $company->getMedia('attachments')->first();

    // Verify all retrieval methods return same file
    expect($retrievedById->id)->toBe($media->id);
    expect($retrievedByCompany->id)->toBe($media->id);
    expect($retrievedByCollection->id)->toBe($media->id);

    // Verify metadata is preserved
    foreach ([$retrievedById, $retrievedByCompany, $retrievedByCollection] as $retrieved) {
        expect($retrieved->file_name)->toBe($filename);
        expect($retrieved->size)->toBe($file->getSize());
        expect($retrieved->mime_type)->toBe('application/pdf');
        expect($retrieved->getCustomProperty('uploaded_by'))->toBe($owner->getKey());
        expect($retrieved->created_at)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
        expect($retrieved->created_at->diffInSeconds($uploadTime))->toBeLessThan(5);
    }

    // Verify file content is accessible
    $storedContent = Storage::disk('public')->get($media->getPath());
    expect($storedContent)->toBe($fileContent);
})->repeat(100);

// Property 34: Document attachment lifecycle - file deletion
test('property: attached documents can be deleted with proper cleanup', function (): void {
    Storage::fake('public');

    $team = Team::factory()->create();
    $owner = User::factory()->create();
    $team->users()->attach($owner);

    $company = Company::factory()
        ->for($team)
        ->create(['account_owner_id' => $owner->getKey()]);

    $filename = fake()->word() . '.docx';
    $file = UploadedFile::fake()->create($filename, 500);

    // Upload file
    $media = $company->addMediaFromRequest('file')
        ->usingFileName($filename)
        ->toMediaCollection('attachments');

    $mediaId = $media->id;
    $filePath = $media->getPath();

    // Verify file exists
    expect($company->attachments)->toHaveCount(1);
    Storage::disk('public')->assertExists($filePath);

    // Delete the file
    $media->delete();

    // Verify file is removed from database
    expect(Media::find($mediaId))->toBeNull();
    expect($company->fresh()->attachments)->toHaveCount(0);

    // Verify file is removed from storage
    Storage::disk('public')->assertMissing($filePath);
})->repeat(100);

// Property 34: Document attachment lifecycle - uploader tracking
test('property: document uploader is tracked and retrievable', function (): void {
    Storage::fake('public');

    $team = Team::factory()->create();
    $owner = User::factory()->create();
    $uploader = User::factory()->create();
    $team->users()->attach([$owner, $uploader]);

    $company = Company::factory()
        ->for($team)
        ->create(['account_owner_id' => $owner->getKey()]);

    $filename = fake()->word() . '.xlsx';
    $file = UploadedFile::fake()->create($filename, 200);

    // Upload file as different user
    $media = $company->addMediaFromRequest('file')
        ->usingFileName($filename)
        ->withCustomProperties([
            'uploaded_by' => $uploader->getKey(),
            'upload_timestamp' => now()->toISOString(),
        ])
        ->toMediaCollection('attachments');

    // Verify uploader is tracked
    expect($media->getCustomProperty('uploaded_by'))->toBe($uploader->getKey());

    // Verify uploader can be retrieved
    $uploaderFromProperty = User::find($media->getCustomProperty('uploaded_by'));
    expect($uploaderFromProperty->id)->toBe($uploader->id);
    expect($uploaderFromProperty->name)->toBe($uploader->name);

    // Verify upload timestamp is stored
    $uploadTimestamp = $media->getCustomProperty('upload_timestamp');
    expect($uploadTimestamp)->not->toBeNull();
    expect(\Illuminate\Support\Facades\Date::parse($uploadTimestamp))->toBeInstanceOf(\Illuminate\Support\Carbon::class);
})->repeat(100);

// Property 34: Document attachment lifecycle - file type validation
test('property: supported file types are accepted and stored correctly', function (): void {
    Storage::fake('public');

    $team = Team::factory()->create();
    $owner = User::factory()->create();
    $team->users()->attach($owner);

    $company = Company::factory()
        ->for($team)
        ->create(['account_owner_id' => $owner->getKey()]);

    // Test various supported file types
    $supportedTypes = [
        'pdf' => 'application/pdf',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
    ];

    $selectedType = fake()->randomElement(array_keys($supportedTypes));
    $expectedMimeType = $supportedTypes[$selectedType];

    $filename = fake()->word() . '.' . $selectedType;
    $file = UploadedFile::fake()->create($filename, 300, $expectedMimeType);

    // Upload file
    $media = $company->addMediaFromRequest('file')
        ->usingFileName($filename)
        ->toMediaCollection('attachments');

    // Verify file type is preserved
    expect($media->mime_type)->toBe($expectedMimeType);
    expect($media->getExtensionAttribute())->toBe($selectedType);
    expect($media->file_name)->toEndWith('.' . $selectedType);

    // Verify file is accessible
    expect($company->attachments)->toHaveCount(1);
    expect($company->attachments->first()->id)->toBe($media->id);
})->repeat(100);

// Property 34: Document attachment lifecycle - metadata preservation through operations
test('property: document metadata is preserved through company operations', function (): void {
    Storage::fake('public');

    $team = Team::factory()->create();
    $owner = User::factory()->create();
    $team->users()->attach($owner);

    $company = Company::factory()
        ->for($team)
        ->create(['account_owner_id' => $owner->getKey()]);

    $filename = fake()->word() . '.pdf';
    $file = UploadedFile::fake()->create($filename, 400);

    // Upload file with metadata
    $originalMetadata = [
        'uploaded_by' => $owner->getKey(),
        'category' => fake()->randomElement(['contract', 'proposal', 'invoice']),
        'description' => fake()->sentence(),
        'version' => fake()->numberBetween(1, 10),
    ];

    $media = $company->addMediaFromRequest('file')
        ->usingFileName($filename)
        ->withCustomProperties($originalMetadata)
        ->toMediaCollection('attachments');

    // Perform various company operations
    $company->update([
        'name' => fake()->company(),
        'website' => fake()->url(),
        'employee_count' => fake()->numberBetween(10, 1000),
        'description' => fake()->paragraph(),
    ]);

    // Refresh company and verify attachment metadata is preserved
    $company->refresh();
    $attachment = $company->attachments->first();

    expect($attachment->id)->toBe($media->id);
    expect($attachment->file_name)->toBe($filename);

    foreach ($originalMetadata as $key => $value) {
        expect($attachment->getCustomProperty($key))->toBe($value);
    }

    // Verify file still exists in storage
    Storage::disk('public')->assertExists($media->getPath());
})->repeat(100);

// Property 34: Document attachment lifecycle - collection isolation
test('property: attachments collection is isolated from other media collections', function (): void {
    Storage::fake('public');

    $team = Team::factory()->create();
    $owner = User::factory()->create();
    $team->users()->attach($owner);

    $company = Company::factory()
        ->for($team)
        ->create(['account_owner_id' => $owner->getKey()]);

    // Upload logo (different collection)
    $logoFile = UploadedFile::fake()->image('logo.png', 200, 200);
    $logo = $company->addMediaFromRequest('logo')
        ->usingFileName('logo.png')
        ->toMediaCollection('logo');

    // Upload attachment
    $attachmentFile = UploadedFile::fake()->create('document.pdf', 500);
    $attachment = $company->addMediaFromRequest('attachment')
        ->usingFileName('document.pdf')
        ->toMediaCollection('attachments');

    // Verify collections are separate
    expect($company->getMedia('logo'))->toHaveCount(1);
    expect($company->getMedia('attachments'))->toHaveCount(1);
    expect($company->attachments)->toHaveCount(1);

    // Verify attachments() method only returns attachments collection
    expect($company->attachments->first()->id)->toBe($attachment->id);
    expect($company->attachments->first()->collection_name)->toBe('attachments');

    // Verify logo is not in attachments
    $attachmentIds = $company->attachments->pluck('id')->toArray();
    expect($attachmentIds)->not->toContain($logo->id);
})->repeat(50);
