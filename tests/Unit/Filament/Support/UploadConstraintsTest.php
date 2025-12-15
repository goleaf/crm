<?php

declare(strict_types=1);

namespace Tests\Unit\Filament\Support;

use App\Filament\Support\UploadConstraints;
use Filament\Forms\Components\FileUpload;
use Tests\TestCase;

final class UploadConstraintsTest extends TestCase
{
    public function test_apply_sets_max_size_from_crm_config(): void
    {
        config([
            'laravel-crm.uploads.max_file_size' => 1234,
            'laravel-crm.uploads.allowed_extensions.documents' => ['pdf'],
        ]);

        $upload = UploadConstraints::apply(
            FileUpload::make('file'),
            types: ['documents'],
        );

        $this->assertSame(1234, $upload->getMaxSize());
    }

    public function test_apply_sets_accepted_file_types_from_extensions(): void
    {
        config([
            'laravel-crm.uploads.allowed_extensions.documents' => ['pdf'],
        ]);

        $upload = UploadConstraints::apply(
            FileUpload::make('file'),
            types: ['documents'],
        );

        $accepted = $upload->getAcceptedFileTypes() ?? [];

        $this->assertContains('application/pdf', $accepted);
    }

    public function test_apply_does_not_set_accepted_types_when_no_extensions_are_configured(): void
    {
        config([
            'laravel-crm.uploads.allowed_extensions.documents' => [],
        ]);

        $upload = UploadConstraints::apply(
            FileUpload::make('file'),
            types: ['documents'],
        );

        $this->assertNull($upload->getAcceptedFileTypes());
    }
}

