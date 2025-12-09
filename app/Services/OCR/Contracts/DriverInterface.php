<?php

declare(strict_types=1);

namespace App\Services\OCR\Contracts;

use App\Services\OCR\DTOs\OCRResult;

interface DriverInterface
{
    /**
     * Extract text from a file.
     *
     * @param string $filePath Full path to the file
     * @param array  $options  Driver-specific options
     */
    public function extract(string $filePath, array $options = []): OCRResult;

    public function getName(): string;
}
