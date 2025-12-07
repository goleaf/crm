<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\PdfGenerationStatus;
use App\Models\PdfGeneration;
use App\Models\PdfTemplate;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class PdfService
{
    /**
     * Generate a PDF from a template and entity data.
     *
     * @param  array<string, mixed>  $mergeData
     * @param  array<string, mixed>  $options
     */
    public function generate(
        PdfTemplate $template,
        Model $entity,
        array $mergeData = [],
        array $options = []
    ): PdfGeneration {
        $generation = PdfGeneration::create([
            'team_id' => $template->team_id,
            'pdf_template_id' => $template->id,
            'entity_type' => $entity::class,
            'entity_id' => $entity->getKey(),
            'merge_data' => $mergeData,
            'generation_options' => $options,
            'status' => PdfGenerationStatus::PROCESSING,
            'file_name' => $this->generateFileName($template, $entity),
        ]);

        try {
            $html = $this->renderTemplate($template, $entity, $mergeData);
            $pdf = $this->createPdf($html, $template, $options);

            if ($template->watermark !== null) {
                $generation->has_watermark = true;
            }

            if ($template->encryption_enabled && $template->encryption_password !== null) {
                $pdf->setEncryption($template->encryption_password);
                $generation->is_encrypted = true;
            }

            $output = $pdf->output();
            $filePath = $this->storePdf($output, $generation->file_name, $template->team_id);

            $generation->update([
                'file_path' => $filePath,
                'file_size' => strlen($output),
                'page_count' => $this->estimatePageCount($output),
                'status' => PdfGenerationStatus::COMPLETED,
            ]);
        } catch (\Throwable $e) {
            $generation->update([
                'status' => PdfGenerationStatus::FAILED,
                'error_message' => $e->getMessage(),
            ]);

            throw $e;
        }

        return $generation->fresh();
    }

    /**
     * Render the template with merge data.
     *
     * @param  array<string, mixed>  $mergeData
     */
    private function renderTemplate(PdfTemplate $template, Model $entity, array $mergeData): string
    {
        $html = $template->layout;

        $allData = array_merge(
            $this->extractEntityData($entity),
            $mergeData
        );

        foreach ($allData as $key => $value) {
            $placeholder = '{{'.$key.'}}';
            $html = str_replace($placeholder, (string) $value, $html);
        }

        if ($template->watermark !== null) {
            return $this->applyWatermark($html, $template->watermark);
        }

        return $html;
    }

    /**
     * Extract data from entity for merge fields.
     *
     * @return array<string, mixed>
     */
    private function extractEntityData(Model $entity): array
    {
        $data = $entity->toArray();
        $extracted = [];

        foreach ($data as $key => $value) {
            if (is_scalar($value) || $value === null) {
                $extracted[$key] = $value;
            }
        }

        return $extracted;
    }

    /**
     * Create PDF with styling and options.
     *
     * @param  array<string, mixed>  $options
     */
    private function createPdf(string $html, PdfTemplate $template, array $options): \Barryvdh\DomPDF\PDF
    {
        $pdf = Pdf::loadHTML($html);

        if ($template->styling !== null) {
            if (isset($template->styling['paper_size'])) {
                $pdf->setPaper($template->styling['paper_size'], $template->styling['orientation'] ?? 'portrait');
            }

            if (isset($template->styling['margins'])) {
                $pdf->setOption('margin_top', $template->styling['margins']['top'] ?? 10);
                $pdf->setOption('margin_right', $template->styling['margins']['right'] ?? 10);
                $pdf->setOption('margin_bottom', $template->styling['margins']['bottom'] ?? 10);
                $pdf->setOption('margin_left', $template->styling['margins']['left'] ?? 10);
            }
        }

        foreach ($options as $key => $value) {
            $pdf->setOption($key, $value);
        }

        return $pdf;
    }

    /**
     * Apply watermark to HTML content.
     *
     * @param  array<string, mixed>  $watermark
     */
    private function applyWatermark(string $html, array $watermark): string
    {
        $text = $watermark['text'] ?? 'CONFIDENTIAL';
        $opacity = $watermark['opacity'] ?? 0.3;
        $rotation = $watermark['rotation'] ?? -45;
        $fontSize = $watermark['font_size'] ?? 72;
        $color = $watermark['color'] ?? '#cccccc';

        $watermarkHtml = sprintf(
            '<div style="position: fixed; top: 50%%; left: 50%%; transform: translate(-50%%, -50%%) rotate(%ddeg); opacity: %s; font-size: %dpx; color: %s; font-weight: bold; z-index: 9999; pointer-events: none; white-space: nowrap;">%s</div>',
            $rotation,
            $opacity,
            $fontSize,
            $color,
            htmlspecialchars($text)
        );

        return str_replace('</body>', $watermarkHtml.'</body>', $html);
    }

    /**
     * Store PDF file.
     */
    private function storePdf(string $content, string $fileName, int $teamId): string
    {
        $path = sprintf('pdfs/team-%d/%s', $teamId, $fileName);
        Storage::disk('local')->put($path, $content);

        return $path;
    }

    /**
     * Generate a unique file name for the PDF.
     */
    private function generateFileName(PdfTemplate $template, Model $entity): string
    {
        $entityName = class_basename($entity);
        $entityId = $entity->getKey();
        $timestamp = now()->format('YmdHis');
        $random = Str::random(8);

        return sprintf('%s-%s-%s-%s-%s.pdf', $template->key, $entityName, $entityId, $timestamp, $random);
    }

    /**
     * Estimate page count from PDF output.
     */
    private function estimatePageCount(string $output): int
    {
        preg_match_all('/\/Page\W/', $output, $matches);

        return max(1, count($matches[0] ?? []));
    }

    /**
     * Check if user has permission to generate PDF.
     */
    public function canGenerate(PdfTemplate $template, ?int $userId = null): bool
    {
        if ($template->permissions === null || $template->permissions === []) {
            return true;
        }

        if ($userId === null) {
            return false;
        }

        $allowedUsers = $template->permissions['users'] ?? [];
        $allowedRoles = $template->permissions['roles'] ?? [];

        if (in_array($userId, $allowedUsers, true)) {
            return true;
        }

        if ($allowedRoles !== []) {
            $user = \App\Models\User::find($userId);
            if ($user !== null) {
                foreach ($allowedRoles as $role) {
                    if ($user->hasRole($role)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Get PDF content from generation record.
     */
    public function getContent(PdfGeneration $generation): string
    {
        if (! Storage::disk('local')->exists($generation->file_path)) {
            throw new \RuntimeException('PDF file not found');
        }

        return Storage::disk('local')->get($generation->file_path);
    }

    /**
     * Delete PDF file and generation record.
     */
    public function delete(PdfGeneration $generation): bool
    {
        if (Storage::disk('local')->exists($generation->file_path)) {
            Storage::disk('local')->delete($generation->file_path);
        }

        return $generation->delete();
    }
}
