<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Date;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

final class InvoicePdfService
{
    /**
     * Generate and attach a PDF for the given invoice.
     */
    public function generate(Invoice $invoice, ?string $templateKey = null): Media
    {
        $templateKey ??= $invoice->template_key ?? config('invoices.default_template', 'standard');
        $view = view()->exists("invoices.templates.{$templateKey}")
            ? "invoices.templates.{$templateKey}"
            : 'invoices.pdf';

        $pdf = Pdf::loadView($view, [
            'invoice' => $invoice->loadMissing(['company', 'contact', 'lineItems', 'payments', 'order']),
            'templateKey' => $templateKey,
        ]);

        $fileName = sprintf('%s.pdf', str_replace(['/', '\\'], '-', $invoice->number ?? 'invoice'));

        return $invoice
            ->addMediaFromString($pdf->output())
            ->usingFileName($fileName)
            ->withCustomProperties([
                'template' => $templateKey,
                'generated_at' => Date::now()->toIso8601String(),
            ])
            ->toMediaCollection('pdfs');
    }
}
