<?php

declare(strict_types=1);

namespace App\Services\Contacts;

use App\Models\People;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;

final class VCardService
{
    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function import(UploadedFile $file): Collection
    {
        $content = $file->getContent();

        if (trim($content) === '') {
            return collect();
        }

        $cards = preg_split('/END:VCARD/i', $content);

        return collect($cards)
            ->filter(fn (?string $card): bool => is_string($card) && str_contains(strtoupper($card), 'BEGIN:VCARD'))
            ->map(fn (string $card): array => $this->parseVCard($card . 'END:VCARD'));
    }

    public function export(People $contact): string
    {
        return $this->generateVCard($contact);
    }

    public function exportMultiple(Collection $contacts): string
    {
        return $contacts
            ->map(fn (People $contact): string => $this->generateVCard($contact))
            ->implode("\n");
    }

    private function parseVCard(string $content): array
    {
        $lines = collect(preg_split('/\r\n|\r|\n/', $content))
            ->map(fn (string $line): array => explode(':', $line, 2))
            ->filter(fn (array $parts): bool => count($parts) === 2);

        $data = [];

        foreach ($lines as [$key, $value]) {
            $upper = strtoupper($key);
            $value = trim($value);

            if ($upper === 'FN') {
                $data['name'] = $value;
            } elseif (str_starts_with($upper, 'EMAIL')) {
                $data['primary_email'] = $value;
            } elseif (str_starts_with($upper, 'TEL')) {
                $data['phone_mobile'] = $value;
            } elseif ($upper === 'ORG') {
                $data['company_name'] = $value;
            } elseif ($upper === 'TITLE') {
                $data['job_title'] = $value;
            } elseif ($upper === 'NOTE') {
                $data['note'] = $value;
            }
        }

        return $data;
    }

    private function generateVCard(People $contact, string $version = '4.0'): string
    {
        $lines = [
            'BEGIN:VCARD',
            "VERSION:{$version}",
            'FN:' . $contact->name,
        ];

        if ($contact->primary_email) {
            $lines[] = 'EMAIL;TYPE=INTERNET:' . $contact->primary_email;
        }

        if ($contact->phone_mobile) {
            $lines[] = 'TEL;TYPE=CELL:' . $contact->phone_mobile;
        }

        if ($contact->job_title) {
            $lines[] = 'TITLE:' . $contact->job_title;
        }

        if ($contact->company?->name) {
            $lines[] = 'ORG:' . $contact->company->name;
        }

        $lines[] = 'END:VCARD';

        return implode("\n", $lines);
    }
}
