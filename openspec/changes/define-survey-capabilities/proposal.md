# Proposal: define-survey-capabilities

## Change ID
- `define-survey-capabilities`

## Summary
- Translate the SuiteCRM Survey feature list into explicit requirements for authoring, distributing, and analyzing surveys, including logic, templates, anonymity controls, and embedding.
- Clarify how survey definitions, questions, schedules, and links flow through campaigns and external channels while preserving response fidelity.

## Capabilities
- `survey-authoring`: Create and design surveys with question types, templates, required flags, branching/logic, previews, and URL generation.
- `survey-distribution`: Distribute surveys through campaigns, schedules, public or anonymous links, and embedded widgets.
- `survey-analytics`: Collect responses, track completion and response rates, and surface results analysis.

## Notes
- `openspec` CLI tooling is not available in this environment; validation must be performed manually outside the repository.
