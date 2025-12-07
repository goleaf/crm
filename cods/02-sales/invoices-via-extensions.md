# Invoices (via extensions)

Status: Implemented

Coverage:
- Done: 12 subfeatures (invoice creation, numbering, line items, status/ledger sync, taxes/discounts, due dates/payment terms, payment tracking, reminders, recurring templates, history, multi-currency fields)
- Partial: 4 (PDF templates/output, payment gateway integration, automated reminders, late-payment workflows)
- Missing: 0 structural pieces from the list

What works now
- Invoice model with auto-numbering/sequence via `InvoiceNumberGenerator`, currency code + FX snapshot, late-fee handling, totals/ledger sync, and status history (`app/Models/Invoice.php`).
- Filament resource with company/contact/opportunity links, status control, issue/due dates, payment terms, currency/FX, late fee percent, and template selector (`app/Filament/Resources/InvoiceResource.php`).
- Line items (name/description/qty/unit price/tax%) with ordering; totals/taxes recalculated on save (`Invoice::syncFinancials()`).
- Payments, reminders, and status history relation managers; overdue filter; recurring template configuration (frequency/interval/start/end/next issue fields).
- Multi-currency supported per invoice via `currency_code` and `fx_rate`; monetary columns cast to decimals.

Gaps / partials
- No PDF generation or email delivery of invoices yet; `template_key` is a selector only.
- Payment tracking is manual; no payment gateway integration or automated late-fee application scheduling.
- Reminder policy is stored but reminder sending/automation is not wired.

Source: docs/suitecrm-features.md (Invoices (via extensions))
