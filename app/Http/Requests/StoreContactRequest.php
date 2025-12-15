<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
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

    public function messages(): array
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

    public function attributes(): array
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
}
