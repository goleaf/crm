# Proposal: define-campaign-capabilities

## Change ID
- `define-campaign-capabilities`

## Summary
- Define the Campaigns module requirements across email and non-email channels, covering creation flows, statuses, targeting, scheduling, and delivery tooling described in the SuiteCRM campaign feature list.
- Clarify how budgeting, revenue/cost capture, ROI, and campaign types interact with target lists, templates, and the sending pipeline.

## Capabilities
- `campaign-planning`: Create email and non-email campaigns with types, statuses, budgets, revenue/cost inputs, and ROI calculations.
- `campaign-targeting-delivery`: Attach target lists, design HTML email templates, and guide users through a wizard to assemble content and recipients.
- `campaign-scheduling`: Schedule sends with timezone-aware timing, support test sends, and execute the delivery plan for the configured channel.

## Notes
- The `openspec` CLI is not available in this environment; validation should be performed manually.
