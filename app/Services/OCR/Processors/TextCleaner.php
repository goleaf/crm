<?php

declare(strict_types=1);

namespace App\Services\OCR\Processors;

use Prism\Prism\Enums\Provider;
use Prism\Prism\Prism;

final class TextCleaner
{
    public function clean(string $text): string
    {
        // Simple passthrough if text is empty
        if (in_array(trim($text), ['', '0'], true)) {
            return $text;
        }

        // Check if enabled in config
        if (! config('ocr.ai.enabled', true)) {
            return $text;
        }

        try {
            $response = (new Prism)->text()
                ->using(Provider::OpenAI, 'gpt-4o')
                ->withPrompt("Fix the following OCR text, correcting only obvious scanning errors (typos, spacing, broken words). maintain original formatting where possible:\n\n".$text)
                ->generate();

            return $response->text;
        } catch (\Throwable $e) {
            // Log error but return original text so flow doesn't break
            logger()->error('OCR AI Cleanup failed: '.$e->getMessage());

            return $text;
        }
    }
}
