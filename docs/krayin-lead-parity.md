# Krayin Lead Creation → LeadResource Parity Map

Purpose: map Krayin’s “Create Leads” steps to our LeadResource UX so we can align field coverage and pipeline defaults without guessing. See `docs/krayin-lead-creation.md` for the source flow.

## Where to create leads in this app
- Filament → Leads → Create (LeadResource/CreateLead page).
- Kanban: Filament → Leads Board uses `LeadStatus` for columns (`new`, `working`, `nurturing`, `qualified`, `unqualified`, `converted`, `recycled`); default is `new`, matching Krayin’s “New” landing stage.

## Field parity against Krayin steps
- Title → `name` (covered).
- Description → `description` textarea added to Lead create/edit.
- Lead Value → `lead_value` numeric field added (USD-format display; stored as decimal).
- Source → `source` (LeadSource enum; covered).
- Type → `lead_type` (enum: new business, existing business).
- Sales Owner → `assigned_to_id` (covered) + table filter added.
- Expected Close Date → `expected_close_date` (date) added.
- Contact Person (Name, Email, Contact Number, Organization) → `name`, `email`, `phone`/`mobile`, `company_name` + optional `company_id` link (covered).
- Products on lead → **not supported** (product lines exist on Opportunities/Quotes, not on Leads).

## Filters parity (Krayin vs LeadResource table)
- Krayin: ID, Lead Value, Sales Person, Contact Person, Lead Type, Source, Tags, Expected Close Date, Created At.
- Ours: `DateScopeFilter` (Created At), `status`, `source`, `lead_type`, `lead_value` range, `expected_close_date` range, `grade`, `assignment_strategy`, `nurture_status`, `creation_source`, `tags`, `assigned_to_id`, soft-delete.
- Gaps: ID search/filter, Contact Person filter (we only search name/email); product filters remain intentionally out-of-scope.

## Recommended backlog to reach parity
1) Add ID filter/search chip for table.  
2) Decide if product line items should stay in Opportunities only or if a lightweight “interested products” relation is required for leads.  
3) If we want “Upload File” parity, scope Magic-AI-style ingestion to import pipeline with `creation_source = import` and default status `new`.  
4) Optional: add contact-person filter (by name/email) for exact parity.
