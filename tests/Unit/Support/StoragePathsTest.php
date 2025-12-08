<?php

declare(strict_types=1);

use App\Support\Paths\StoragePaths;
use Hdaklue\PathBuilder\PathBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    \Illuminate\Support\Facades\Date::setTestNow('2024-01-02 03:04:05');
});
afterEach(function (): void {
    \Illuminate\Support\Facades\Date::setTestNow();
});

it('builds hashed document directories and slugged filenames', function (): void {
    $directory = StoragePaths::documentsDirectory(42);
    $expectedHash = md5('42');

    $fileName = StoragePaths::documentFileName('Team Brief V1.pdf');

    expect($directory)->toBe("documents/{$expectedHash}")
        ->and(PathBuilder::isSafe($directory))->toBeTrue()
        ->and($fileName)->toMatch('/^\\d{14}-[a-z0-9]{6}-team-brief-v1\\.pdf$/');
});

it('builds safe pdf paths with hashed tenant isolation', function (): void {
    Storage::fake('local');

    $entity = new class extends Model
    {
        protected $table = 'fake_entities';

        public $exists = true;

        protected $attributes = ['id' => 15];

        public function getKey(): int
        {
            return $this->attributes['id'];
        }
    };

    $fileName = StoragePaths::pdfFileName('quote-template', $entity);
    $path = StoragePaths::pdfStoragePath(7, $fileName);

    expect($fileName)->toMatch('/^[a-z0-9\\-]+\\.pdf$/')
        ->and($path)->toStartWith('pdfs/'.md5('7').'/')
        ->and(PathBuilder::isSafe($path))->toBeTrue();
});

it('builds hashed profile photo paths and filenames', function (): void {
    $directory = StoragePaths::profilePhotoDirectory(9, 3);
    $fileName = StoragePaths::profilePhotoFileName('Avatar My Face.png');

    expect($directory)->toBe('profile-photos/'.md5('3').'/'.md5('9'))
        ->and(PathBuilder::isSafe($directory))->toBeTrue()
        ->and($fileName)->toMatch('/^\\d{14}-[a-z0-9]{6}-avatar-my-face\\.png$/');
});
