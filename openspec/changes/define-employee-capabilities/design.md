# Design Notes

## Employee Record Surfaces
- Employee records consolidate personal contact details (work/personal email/phone), employment data (department, job title/role, start date, status), reporting structure (manager and direct reports), portal access flags, emergency contacts, and attached documents.
- Skills and certifications live as child collections with effective/expiration dates and verification status so the directory can surface current qualifications and alert on expirations.
- Performance tracking stores period-based reviews, goals, and ratings tied to the employee and their manager/reviewer, keeping history separate from the core profile while still visible from the employee record.

## Directory and Relationships
- The employee directory queries core profile fields, departments, locations, and status, and renders manager/reporting hierarchies; clicking into a person opens the full record with contact options and links to related HR artifacts (skills, documents, reviews).
- Reporting structures are enforced through manager references and cascade to subordinate listings; changes to a manager update subordinate views and breadcrumbs without requiring manual edits on each report.
- Portal access is governed by a flag and credential linkage on the employee record; provisioning enables portal login and self-service actions like viewing documents and requesting time off.

## Integrations and HR Operations
- Employee documents store type, version, uploader, and effective/expiry metadata; sensitive files respect portal visibility rules so only authorized parties can access them.
- Payroll integration uses employee identifiers, employment status, job/department, and start date to sync hire/termination/status changes, while keeping payroll amounts external but referenceable for history.
- Time-off tracking captures balances, accruals, and requests per employee with approval routing through managers; approved time off feeds reporting and can be exported or synced to payroll/leave systems.
