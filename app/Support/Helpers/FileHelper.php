<?php

declare(strict_types=1);

namespace App\Support\Helpers;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class FileHelper
{
    /**
     * Get file extension from filename or path.
     */
    public static function extension(string $filename): string
    {
        return pathinfo($filename, PATHINFO_EXTENSION);
    }

    /**
     * Get filename without extension.
     */
    public static function nameWithoutExtension(string $filename): string
    {
        return pathinfo($filename, PATHINFO_FILENAME);
    }

    /**
     * Check if a file is an image based on extension.
     */
    public static function isImage(string $filename): bool
    {
        $extension = strtolower(self::extension($filename));

        return in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp'], true);
    }

    /**
     * Check if a file is a document based on extension.
     */
    public static function isDocument(string $filename): bool
    {
        $extension = strtolower(self::extension($filename));

        return in_array($extension, ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'csv'], true);
    }

    /**
     * Check if a file is a video based on extension.
     */
    public static function isVideo(string $filename): bool
    {
        $extension = strtolower(self::extension($filename));

        return in_array($extension, ['mp4', 'avi', 'mov', 'wmv', 'flv', 'webm', 'mkv'], true);
    }

    /**
     * Check if a file is an audio file based on extension.
     */
    public static function isAudio(string $filename): bool
    {
        $extension = strtolower(self::extension($filename));

        return in_array($extension, ['mp3', 'wav', 'ogg', 'flac', 'aac', 'm4a'], true);
    }

    /**
     * Get MIME type from file extension.
     */
    public static function mimeType(string $filename): string
    {
        $extension = strtolower(self::extension($filename));

        $mimeTypes = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'svg' => 'image/svg+xml',
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'csv' => 'text/csv',
            'txt' => 'text/plain',
            'mp4' => 'video/mp4',
            'mp3' => 'audio/mpeg',
            'zip' => 'application/zip',
        ];

        return $mimeTypes[$extension] ?? 'application/octet-stream';
    }

    /**
     * Generate a safe filename from a string.
     */
    public static function sanitizeFilename(string $filename): string
    {
        $extension = self::extension($filename);
        $name = self::nameWithoutExtension($filename);

        $safe = Str::slug($name);

        return $extension !== '' && $extension !== '0' ? $safe . '.' . $extension : $safe;
    }

    /**
     * Get file icon class based on file type.
     */
    public static function iconClass(string $filename): string
    {
        if (self::isImage($filename)) {
            return 'heroicon-o-photo';
        }

        if (self::isDocument($filename)) {
            return 'heroicon-o-document-text';
        }

        if (self::isVideo($filename)) {
            return 'heroicon-o-film';
        }

        if (self::isAudio($filename)) {
            return 'heroicon-o-musical-note';
        }

        $extension = strtolower(self::extension($filename));

        return match ($extension) {
            'zip', 'rar', '7z', 'tar', 'gz' => 'heroicon-o-archive-box',
            'pdf' => 'heroicon-o-document',
            default => 'heroicon-o-document',
        };
    }

    /**
     * Get file size from storage.
     */
    public static function size(string $path, ?string $disk = null): int
    {
        return Storage::disk($disk)->size($path);
    }

    /**
     * Check if file exists in storage.
     */
    public static function exists(string $path, ?string $disk = null): bool
    {
        return Storage::disk($disk)->exists($path);
    }

    /**
     * Delete file from storage.
     */
    public static function delete(string $path, ?string $disk = null): bool
    {
        return Storage::disk($disk)->delete($path);
    }

    /**
     * Get temporary URL for a file.
     */
    public static function temporaryUrl(
        string $path,
        \DateTimeInterface $expiration,
        ?string $disk = null,
    ): string {
        return Storage::disk($disk)->temporaryUrl($path, $expiration);
    }

    /**
     * Validate uploaded file.
     */
    public static function validateUpload(
        UploadedFile $file,
        array $allowedExtensions = [],
        ?int $maxSize = null,
    ): bool {
        if ($allowedExtensions !== [] && ! in_array(
            strtolower($file->getClientOriginalExtension()),
            array_map(strtolower(...), $allowedExtensions),
            true,
        )) {
            return false;
        }

        if ($maxSize !== null && $file->getSize() > $maxSize) {
            return false;
        }

        return true;
    }
}
