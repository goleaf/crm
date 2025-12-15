# Krayin CRM Packages

Source: [Krayin CRM Developer Portal - Packages](https://devdocs.krayincrm.com/2.1/architecture/packages.html#introduction)

## Introduction
- Krayin ships its core features as Laravel packages so teams can add or customize functionality using standard package patterns.
- Each package uses a service provider to load routes, migrations, localization, and publishable assets, keeping features modular.

## Default packages
### Admin
- Admin dashboard for monitoring leads started/over time, top leads/customers/products, pipeline health, activities, and email engagement.

### Activity
- Scheduler for calls, meetings, and lunches with calendar integration, reminders, and follow-ups logged against records.

### Attribute
- Manages custom attributes for leads, persons, organizations, products, and quotes with typed fields, defaults, visibility, and search settings.

### Contact
- Handles organizations and people records with creation flows, contact info capture, and linkage to other CRM entities.

### Core
- Shared infrastructure such as system settings, configurations, and common utilities relied on by all packages.

### Email
- Email workspace covering compose (rich text and attachments), inbox management, drafts/outbox/sent mail, and trash recovery.

### EmailTemplate
- Stores reusable templates (name, subject, body) to keep outbound email messaging consistent.

### Installer
- CLI and GUI installer that configures environments, installs dependencies, provisions databases, and runs migrations/post-install steps.

### Lead
- Captures and tracks leads through pipeline stages, assigns owners, and links contact persons plus interested products.

### Product
- Maintains catalog data including names, descriptions, SKUs, quantities, pricing, and related metadata.

### Quote
- Builds sales quotes with owners, subjects/descriptions, expiration dates, associated persons/leads, addresses, and financial breakdowns (discounts, tax, adjustments, totals).

### Tag
- Categorization system with names, colors, and user ownership applied across CRM entities.

### User
- Manages groups, roles/permissions, and user accounts with authentication and authorization controls.

### WebForm
- Configurable forms that map fields to CRM data, capture submissions from embeds/links, and trigger notifications or automation.

### Automation
- Workflows and webhooks for task automation, conditional logic, scheduled actions, and real-time outbound integrations.

### Datagrid
- Configurable data tables with column setup, filtering, sorting, and pagination for admin-facing lists.

### Warehouse
- Stores warehouse profiles with contact details, descriptions, and full addresses to support inventory and logistics.
