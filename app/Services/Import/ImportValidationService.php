<?php

declare(strict_types=1);

namespace App\Services\Import;

use App\Models\ImportJob;
use Illuminate\Support\Facades\Validator;

final class ImportValidationService
{
    public function validateMapping(ImportJob $importJob, array $mapping): array
    {
        $errors = [];
        $modelClass = $this->getModelClass($importJob->model_type);

        if (! $modelClass) {
            $errors[] = "Invalid model type: {$importJob->model_type}";

            return $errors;
        }

        // Get model fillable fields
        $model = new $modelClass;
        $fillableFields = $model->getFillable();

        // Validate required fields are mapped
        $requiredFields = $this->getRequiredFields($importJob->model_type);
        foreach ($requiredFields as $field) {
            if (! isset($mapping[$field]) || empty($mapping[$field])) {
                $errors[] = "Required field '{$field}' is not mapped";
            }
        }

        // Validate mapped fields exist in model
        foreach (array_keys($mapping) as $modelField) {
            if (! in_array($modelField, $fillableFields)) {
                $errors[] = "Field '{$modelField}' is not fillable in {$importJob->model_type} model";
            }
        }

        return $errors;
    }

    public function validateRow(array $row, array $mapping, string $modelType): array
    {
        $errors = [];
        $data = [];

        // Map CSV columns to model fields
        foreach ($mapping as $modelField => $csvColumn) {
            $data[$modelField] = $row[$csvColumn] ?? null;
        }

        // Get validation rules for the model
        $rules = $this->getValidationRules($modelType);

        // Validate the data
        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $errors[] = $error;
            }
        }

        return $errors;
    }

    private function getModelClass(string $modelType): ?string
    {
        $modelMap = [
            'Company' => \App\Models\Company::class,
            'People' => \App\Models\People::class,
            'Contact' => \App\Models\Contact::class,
            'Lead' => \App\Models\Lead::class,
            'Opportunity' => \App\Models\Opportunity::class,
            'Task' => \App\Models\Task::class,
            'Note' => \App\Models\Note::class,
        ];

        return $modelMap[$modelType] ?? null;
    }

    private function getRequiredFields(string $modelType): array
    {
        $requiredFields = [
            'Company' => ['name'],
            'People' => ['first_name', 'last_name'],
            'Contact' => ['name'],
            'Lead' => ['name'],
            'Opportunity' => ['title'],
            'Task' => ['title'],
            'Note' => ['title'],
        ];

        return $requiredFields[$modelType] ?? [];
    }

    private function getValidationRules(string $modelType): array
    {
        $rules = [
            'Company' => [
                'name' => 'required|string|max:255',
                'email' => 'nullable|email|max:255',
                'phone' => 'nullable|string|max:50',
                'website' => 'nullable|url|max:255',
            ],
            'People' => [
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'email' => 'nullable|email|max:255',
                'phone' => 'nullable|string|max:50',
            ],
            'Contact' => [
                'name' => 'required|string|max:255',
                'email' => 'nullable|email|max:255',
                'phone' => 'nullable|string|max:50',
            ],
            'Lead' => [
                'name' => 'required|string|max:255',
                'email' => 'nullable|email|max:255',
                'phone' => 'nullable|string|max:50',
            ],
            'Opportunity' => [
                'title' => 'required|string|max:255',
                'value' => 'nullable|numeric|min:0',
                'close_date' => 'nullable|date',
            ],
            'Task' => [
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'due_date' => 'nullable|date',
            ],
            'Note' => [
                'title' => 'required|string|max:255',
                'content' => 'nullable|string',
            ],
        ];

        return $rules[$modelType] ?? [];
    }
}
