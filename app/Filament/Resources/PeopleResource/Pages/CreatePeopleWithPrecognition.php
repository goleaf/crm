<?php

declare(strict_types=1);

namespace App\Filament\Resources\PeopleResource\Pages;

use App\Filament\Resources\PeopleResource;
use App\Models\People;
use Filament\Forms;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

final class CreatePeopleWithPrecognition extends CreateRecord
{
    protected static string $resource = PeopleResource::class;

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('app.labels.customer_information'))
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label(__('app.labels.name'))
                                    ->required()
                                    ->maxLength(255)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get): void {
                                        $this->validateFieldPrecognitively('name', $state);
                                    }),

                                Forms\Components\TextInput::make('email')
                                    ->label(__('app.labels.email'))
                                    ->email()
                                    ->required()
                                    ->maxLength(255)
                                    ->unique(People::class, 'email', ignoreRecord: true)
                                    ->live(debounce: 500)
                                    ->afterStateUpdated(function ($state): void {
                                        $this->validateFieldPrecognitively('email', $state);
                                    })
                                    ->helperText(fn ($state, $component): ?string => $this->getValidationHelperText('email', $state),
                                    ),

                                Forms\Components\TextInput::make('phone')
                                    ->label(__('app.labels.phone'))
                                    ->tel()
                                    ->maxLength(50)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function ($state): void {
                                        $this->validateFieldPrecognitively('phone', $state);
                                    }),

                                Forms\Components\TextInput::make('mobile')
                                    ->label(__('app.labels.mobile'))
                                    ->tel()
                                    ->maxLength(50)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function ($state): void {
                                        $this->validateFieldPrecognitively('mobile', $state);
                                    }),

                                Forms\Components\Select::make('company_id')
                                    ->label(__('app.labels.company'))
                                    ->relationship('company', 'name')
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->afterStateUpdated(function ($state): void {
                                        $this->validateFieldPrecognitively('company_id', $state);
                                    }),

                                Forms\Components\TextInput::make('title')
                                    ->label(__('app.labels.title'))
                                    ->maxLength(255)
                                    ->live(onBlur: true),

                                Forms\Components\TextInput::make('department')
                                    ->label(__('app.labels.department'))
                                    ->maxLength(255)
                                    ->live(onBlur: true),

                                Forms\Components\Select::make('persona_id')
                                    ->label(__('app.labels.persona'))
                                    ->relationship('persona', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->live(),
                            ]),

                        Forms\Components\Textarea::make('address')
                            ->label(__('app.labels.address'))
                            ->maxLength(1000)
                            ->rows(3)
                            ->live(onBlur: true),
                    ]),
            ]);
    }

    /**
     * Validate a single field precognitively using the same rules as Form Request
     */
    private function validateFieldPrecognitively(string $field, mixed $value): void
    {
        $rules = $this->getValidationRules();

        if (! isset($rules[$field])) {
            return;
        }

        $validator = Validator::make(
            [$field => $value],
            [$field => $rules[$field]],
            $this->getValidationMessages(),
            $this->getValidationAttributes(),
        );

        if ($validator->fails()) {
            $this->addError("data.{$field}", $validator->errors()->first($field));
        } else {
            // Clear any existing errors for this field
            $errorBag = $this->getErrorBag();
            $errors = $errorBag->getMessages();
            unset($errors["data.{$field}"]);

            // Create new error bag without the cleared error
            $newBag = new \Illuminate\Support\MessageBag($errors);
            foreach ($newBag->keys() as $key) {
                $this->addError($key, $newBag->first($key));
            }
        }
    }

    /**
     * Get validation rules matching StoreContactRequest
     */
    private function getValidationRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('people', 'email')
                    ->where('team_id', auth()->user()?->currentTeam?->id),
            ],
            'phone' => ['nullable', 'string', 'max:50'],
            'mobile' => ['nullable', 'string', 'max:50'],
            'company_id' => ['required', 'exists:companies,id'],
            'title' => ['nullable', 'string', 'max:255'],
            'department' => ['nullable', 'string', 'max:255'],
            'persona_id' => ['nullable', 'exists:contact_personas,id'],
            'address' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Get validation messages matching StoreContactRequest
     */
    private function getValidationMessages(): array
    {
        return [
            'name.required' => __('app.validation.contact_name_required'),
            'email.required' => __('app.validation.email_required'),
            'email.email' => __('app.validation.email_invalid'),
            'email.unique' => __('app.validation.email_already_exists'),
            'company_id.required' => __('app.validation.company_required'),
            'company_id.exists' => __('app.validation.company_not_found'),
            'persona_id.exists' => __('app.validation.persona_not_found'),
        ];
    }

    /**
     * Get validation attributes matching StoreContactRequest
     */
    protected function getValidationAttributes(): array
    {
        return [
            'name' => __('app.labels.name'),
            'email' => __('app.labels.email'),
            'phone' => __('app.labels.phone'),
            'mobile' => __('app.labels.mobile'),
            'company_id' => __('app.labels.company'),
            'title' => __('app.labels.title'),
            'department' => __('app.labels.department'),
            'persona_id' => __('app.labels.persona'),
            'address' => __('app.labels.address'),
        ];
    }

    /**
     * Get helper text for validation feedback
     */
    private function getValidationHelperText(string $field, mixed $value): ?string
    {
        if (empty($value)) {
            return null;
        }

        $errors = $this->getErrorBag()->getMessages();

        if (isset($errors["data.{$field}"])) {
            return null; // Error will be shown by Filament
        }

        // Show success indicator for unique fields
        if ($field === 'email') {
            return '✓ ' . __('app.messages.email_available');
        }

        return null;
    }
}
