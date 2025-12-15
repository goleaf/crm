<?php

declare(strict_types=1);

namespace App\Filament\Support;

use App\Support\CrmConfig;
use Filament\Forms\Components\FileUpload;
use Symfony\Component\Mime\MimeTypes;

final class UploadConstraints
{
    /**
     * @param array<int, string> $types Keys from `laravel-crm.uploads.allowed_extensions` (e.g. documents/images/archives).
     */
    public static function apply(FileUpload $upload, array $types, ?int $maxSizeKb = null): FileUpload
    {
        $extensions = collect($types)
            ->flatMap(fn (string $type): array => CrmConfig::allowedFileExtensions($type))
            ->map(fn (mixed $extension): string => strtolower(trim((string) $extension)))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $maxSizeKb ??= CrmConfig::maxFileSize();

        if ($extensions !== []) {
            $upload->rules([
                'mimes:' . implode(',', $extensions),
            ]);

            $mimeTypes = self::mimeTypesForExtensions($extensions);

            if ($mimeTypes !== []) {
                $upload->acceptedFileTypes($mimeTypes);
            }
        }

        return $upload->maxSize($maxSizeKb);
    }

    /**
     * @param array<int, string> $extensions
     *
     * @return array<int, string>
     */
    private static function mimeTypesForExtensions(array $extensions): array
    {
        $mapper = MimeTypes::getDefault();

        $mimeTypes = collect($extensions)
            ->flatMap(fn (string $extension): array => $mapper->getMimeTypes($extension))
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();

        return $mimeTypes;
    }
}
