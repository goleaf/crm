<?php

declare(strict_types=1);

namespace App\Support\Paths;

use Hdaklue\PathBuilder\Enums\SanitizationStrategy;
use Hdaklue\PathBuilder\PathBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

final class StoragePaths
{
    public static function documentsDirectory(?int $teamId): string
    {
        $builder = PathBuilder::base('documents');

        if ($teamId !== null) {
            $builder = $builder->addHashedDir((string) $teamId);
        }

        return $builder->validate()->toString();
    }

    public static function documentFileName(string $originalName): string
    {
        return self::sanitizeFileName(self::uniqueName($originalName));
    }

    public static function pdfFileName(string $templateKey, Model $entity): string
    {
        $uniqueName = implode('-', [
            $templateKey,
            Str::slug(class_basename($entity)),
            (string) $entity->getKey(),
            now()->format('YmdHis'),
            Str::random(8),
        ]);

        return self::sanitizeFileName("{$uniqueName}.pdf");
    }

    public static function pdfStoragePath(int $teamId, string $fileName): string
    {
        return PathBuilder::base('pdfs')
            ->addHashedDir((string) $teamId)
            ->addFile($fileName, SanitizationStrategy::SLUG)
            ->validate()
            ->mustNotExist('local')
            ->toString();
    }

    public static function profilePhotoDirectory(int $userId, ?int $teamId): string
    {
        $builder = PathBuilder::base('profile-photos');

        if ($teamId !== null) {
            $builder = $builder->addHashedDir((string) $teamId);
        }

        return $builder
            ->addHashedDir((string) $userId)
            ->validate()
            ->toString();
    }

    public static function profilePhotoFileName(string $originalName): string
    {
        return self::sanitizeFileName(self::uniqueName($originalName));
    }

    private static function sanitizeFileName(string $fileName): string
    {
        return PathBuilder::base('')
            ->addFile($fileName, SanitizationStrategy::SLUG)
            ->getFilename();
    }

    private static function uniqueName(string $fileName): string
    {
        return sprintf('%s-%s-%s', now()->format('YmdHis'), Str::random(6), $fileName);
    }
}
