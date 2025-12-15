---
inclusion: always
---

# Laravel Precognition Integration

## Overview
- Laravel Precognition provides real-time frontend validation by running backend validation rules before form submission, creating seamless UX with instant feedback while maintaining server-side validation as the single source of truth.
- Built into Laravel 12+; no additional packages required for backend support.
- Frontend packages available for Vue 3, React, and Alpine.js.

## Backend Implementation

### Form Requests
- Always use Form Requests for validation logic; Precognition automatically respects these rules.
- Type-hint Form Requests in controller methods to ensure validation runs.
- Use `Rule::unique()` with `->ignore()` for update operations to prevent false positives.
- Leverage translated validation messages via `messages()` method.

### Controllers
- Precognitive requests automatically stop after validation; only actual submissions reach the action logic.
- No special handling needed in controller methods—Form Request validation handles everything.
- Return appropriate HTTP status codes: 204 for successful precognitive validation, 422 for validation errors, 201/200 for actual submissions.

### Middleware
- Ensure `HandlePrecognitiveRequests` middleware is prepended to API routes in `bootstrap/app.php`.
- Configure CORS to allow Precognition headers: `Precognition`, `Precognition-Validate-Only`, `Precognition-Success`.

## Frontend Integration (Vue 3)

### Basic Pattern
- Use `useForm()` composable from `laravel-precognition-vue` to create reactive forms.
- Trigger validation with `form.validate('field')` or `form.validate(['field1', 'field2'])`.
- Check validation state with `form.invalid('field')`, `form.valid('field')`, `form.hasErrors`.
- Display errors with `form.errors.field`.

### Validation Triggers
- **Text inputs**: Validate on blur (`@blur="form.validate('field')"`) to avoid excessive API calls.
- **Email/unique fields**: Use debounced validation (500ms) to check availability as user types.
- **Select/radio**: Validate immediately on change (`@change="form.validate('field')"`).
- **Checkboxes**: Validate on change for required checkboxes.

### Performance
- Always debounce validation for text inputs to prevent excessive API calls.
- Use `watchDebounced` from `@vueuse/core` for reactive debouncing.
- Validate only changed fields, not entire form on every keystroke.
- Previous validation requests are automatically cancelled when new ones are made.

## Filament Integration

### Livewire Components
- Use `->precognitive()` for text inputs (defaults to `onBlur: true`).
- Use `->precognitive(debounce: 500)` for email/unique fields to validate as user types.
- The macro handles `live()` and `afterStateUpdated()` automatically.

### Actions
- Add `->precognitive()` to form fields within actions to enable real-time validation.
- Leverage Filament's built-in validation; Precognition enhances it with real-time feedback.

## Testing

### Feature Tests
- Test precognitive validation by adding `Precognition: true` header to requests.
- Use `Precognition-Validate-Only` header to validate specific fields.
- Assert 204 status for successful validation, 422 for validation errors.
- Verify no data is saved during precognitive validation (check database counts).
- Test actual submissions without Precognition headers to ensure data is saved.

### Test Patterns
```php
// Precognitive validation
->postJson('/api/resource', $data, [
    'Precognition' => 'true',
    'Precognition-Validate-Only' => 'email',
])
->assertStatus(204); // or 422 for errors

// Actual submission
->postJson('/api/resource', $data)
->assertStatus(201);
```

## Best Practices

### DO:
- ✅ Use Form Requests for all validation logic
- ✅ Debounce validation for text inputs (300-500ms)
- ✅ Validate on blur for better UX
- ✅ Show positive feedback for valid fields (✓ Email is available)
- ✅ Disable submit button during processing or when errors exist
- ✅ Handle network errors gracefully
- ✅ Use translated validation messages
- ✅ Test both precognitive and actual submissions

### DON'T:
- ❌ Validate on every keystroke without debouncing
- ❌ Skip backend validation (Precognition is UX enhancement, not security)
- ❌ Forget to configure CORS for Precognition headers
- ❌ Use generic Request type-hint instead of Form Request
- ❌ Ignore rate limiting on validation endpoints
- ❌ Trust client-side validation alone
- ❌ Forget to test precognitive validation separately

## Security
- Always validate on backend; Precognition is a UX enhancement, not a security feature.
- Use Form Requests to centralize validation logic.
- Rate limit API endpoints to prevent abuse of validation endpoints.
- Sanitize all inputs; never trust client-side data.
- Ensure CSRF protection is properly configured with Sanctum/session authentication.

## Common Patterns

### Multi-Step Forms
- Validate each step's fields before allowing progression to next step.
- Use `await form.validate(['field1', 'field2'])` to validate multiple fields.
- Check `form.hasErrors` before incrementing step counter.

### Conditional Fields
- Clear and re-validate fields when conditions change.
- Watch for changes in conditional triggers and validate dependent fields.

### File Uploads
- Validate file type, size, and other constraints in real-time.
- Use `form.validate('file')` after file selection.

## Related Documentation
- `docs/laravel-precognition.md` - Comprehensive integration guide with examples
- `docs/filament-forms-inputs.md` - Filament form field patterns
- `docs/pest-laravel-expectations.md` - Testing patterns for HTTP assertions
