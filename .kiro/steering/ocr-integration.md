---
inclusion: always
---

# OCR Integration Guidelines

## Service Architecture
- OCR services follow the container pattern with constructor injection and readonly properties.
- Register OCR services in `AppServiceProvider::register()` using `singleton()` for stateful services (drivers, managers) and `bind()` for stateless processors.
- Use driver pattern for multiple OCR engines (SpaceOCR, Tesseract, Google Vision) with a common interface (`DriverInterface`).
- All OCR processing should be queue-based for files larger than 1MB or when processing multiple documents.

## File Handling
- Validate file types (PDF, PNG, JPG, JPEG, GIF) and sizes (max 10MB) before processing.
- Store uploaded files in `storage/app/ocr/uploads` with unique identifiers.
- Store processed results in `storage/app/ocr/results` with JSON format.
- Clean up temporary files after processing completes or fails.
- Use default `cdsmths/laravel-ocr-space` integration via custom Service wrapper.

## Template System
- Document templates define extractable fields with regex patterns and validation rules.
- Templates support multiple document types: invoices, receipts, business cards, contracts, shipping labels.
- Field definitions include: name, type (string, number, date, email), required flag, validation rules, extraction pattern.
- Confidence scoring (0-1) indicates extraction accuracy; reject results below configured threshold (default 0.7).
- Templates are cached per-tenant to avoid repeated database queries.

## Filament v4.3+ Integration
- Create OCR resources in `app/Filament/Resources/OCR/` namespace.
- Use FileUpload component with `->acceptedFileTypes()` and `->maxSize()` validation.
- Display processing status with badge colors: pending (gray), processing (warning), completed (success), failed (danger).
- Show confidence scores as percentages with color coding: >90% (success), 70-90% (warning), <70% (danger).
- Provide reprocess action for failed documents with confirmation modal.
- Use KeyValue component for displaying extracted data in view pages.

## AI Enhancement
- Use Prism PHP (already in composer.json) for text cleanup and normalization.
- AI cleanup improves OCR accuracy by fixing common errors (spacing, punctuation, formatting).
- Configure AI model, temperature, and max tokens in `config/ocr.php`.
- AI processing is optional and can be disabled via `OCR_AI_ENABLED=false`.
- Log AI cleanup results for quality monitoring and improvement.

## Queue Processing
- Use dedicated `ocr-processing` queue for OCR jobs.
- Set appropriate timeout (300s default) for large documents.
- Implement retry logic with exponential backoff (3 attempts).
- Dispatch events for processing lifecycle: started, completed, failed.
- Update document status in database at each stage.

## Testing
- Test OCR service with various document qualities (high, medium, low).
- Mock Tesseract driver in unit tests to avoid external dependencies.
- Use fixture files in `tests/fixtures/ocr/` for consistent test data.
- Test template matching with known document samples.
- Verify confidence scoring accuracy with labeled test set.
- Test queue processing with `Queue::fake()` and assert job dispatched.

## Performance
- Cache template definitions per-tenant (1 hour TTL).
- Process images in batches when handling multiple documents.
- Use image preprocessing (resize, contrast, denoise) to improve accuracy and speed.
- Monitor processing times and optimize slow templates.
- Consider cloud OCR APIs (Google Vision, AWS Textract) for high-volume scenarios.

## Security
- Validate and sanitize all file uploads.
- Store sensitive documents with encryption at rest.
- Implement access control for OCR results based on user permissions.
- Log all OCR operations with user context for audit trail.
- Redact sensitive data (SSN, credit cards) from extracted text if configured.

## Error Handling
- Catch and log OCR exceptions with full context (file path, template, user).
- Provide user-friendly error messages in Filament notifications.
- Store failed processing attempts with error details for debugging.
- Implement fallback to manual data entry when OCR fails.
- Send admin notifications for repeated OCR failures.

## Translation
- All OCR-related UI text uses translation keys in `lang/*/ocr.php`.
- Template names and field labels support multiple languages.
- Error messages are translated and context-aware.
- Document type labels use enum translations in `lang/*/enums.php`.

## Best Practices
- ✅ Always validate files before processing
- ✅ Use queue-based processing for all OCR operations
- ✅ Cache templates to reduce database queries
- ✅ Provide confidence scores with all extractions
- ✅ Log processing times for performance monitoring
- ✅ Test with real-world document samples
- ✅ Implement retry logic for transient failures
- ✅ Clean up temporary files after processing

## Don'ts
- ❌ Don't process large files synchronously
- ❌ Don't skip file validation
- ❌ Don't ignore confidence scores
- ❌ Don't hardcode extraction patterns
- ❌ Don't store raw OCR text without cleanup
- ❌ Don't forget to handle processing failures
- ❌ Don't expose sensitive data in logs

## Related Documentation
- `docs/ocr-integration-strategy.md` - Integration strategy and options
- `docs/laravel-container-services.md` - Service pattern guidelines
- `.kiro/steering/filament-conventions.md` - Filament resource patterns
- `.kiro/steering/testing-standards.md` - Testing requirements
