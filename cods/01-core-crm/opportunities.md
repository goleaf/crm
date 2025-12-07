# Opportunities

Status: Partial

Coverage:
- Done: 3 subfeatures (opportunity records, account/contact linkage, team collaboration via collaborators)
- Partial: 3 (amount/stage/close-date possible via custom fields but no baked-in calculations)
- Missing: 11 (pipeline mgmt, probability/weighting, competitor tracking, quotes linkage, forecasting hooks, dashboards, win/loss analytics)

Details
- Opportunity CRUD with required name plus company/contact selectors and multi-user collaborators (`app/Filament/Resources/OpportunityResource.php`).
- Custom fields can be added to track amount, stage, probability, expected close, competitors, and next steps, but no native calculations or UI cues are provided.
- Exports and soft-delete handling are available; relations to tasks/notes cover next-step documentation manually.

Gaps
- No sales stages/probability/weighted revenue or expected close calculations.
- No pipeline views, dashboards, or forecasting rollups; no competitor tracking entities.
- No linkage to quotes/forecast modules (these modules do not exist yet).

Source: docs/suitecrm-features.md (Opportunities)
