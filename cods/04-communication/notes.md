# Notes

Status: Implemented

Coverage:
- Done: 12 subfeatures (note creation on any record, rich body via custom field, attachments, privacy/visibility, timestamps/authors, categories/tags, relationships, history, templates, print view)
- Partial: 2 (export/search UX depth, shared notes UX)
- Missing: 0 core areas

What works now
- Notes attach to companies/people/opportunities/cases/leads/tasks via morphs; creation source tracked and soft deletes enabled (`app/Models/Note.php`).
- Visibility controls (`INTERNAL`, `PRIVATE`, `EXTERNAL`) plus categories and template flag; author captured via `HasCreator`.
- Attachments via media library with dedicated collection; histories tracked in `note_histories` for edits (`app/Models/NoteHistory.php`).
- Body stored through Relaticle custom field `NoteField::BODY`; helpers for plain text extraction.
- Print-friendly view at `/notes/{note}/print` handled by `App\Http\Controllers\NotePrintController`.

Gaps / partials
- No dedicated shared/locked note UI beyond visibility flag.
- Search/export exist per-resource tables but no global note search or cross-module note templates library.

Source: docs/suitecrm-features.md (Notes)
