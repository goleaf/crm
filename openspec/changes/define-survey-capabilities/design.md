# Design Notes

## Authoring model
- Surveys comprise a header (title, description, status, availability window), a sequence of questions, and optional templates; questions store type metadata (multiple choice options, rating scales, text limits) plus required flags so rendering and validation can be consistent across web, email, and embedded contexts.
- Logic/branching rules live alongside questions as conditional jumps or display predicates to keep navigation deterministic and to allow previews to simulate skip paths without persisting responses.
- Template inheritance should separate visual layout from question content so teams can reuse styling while cloning only the question set when needed.

## Distribution and identity
- Each survey instance generates unique URLs and optional anonymous tokens; when distributed via campaigns, recipient and campaign IDs are embedded for attribution while anonymous links omit identifying parameters and enforce non-trackable response storage.
- Scheduling stores start/end windows and queueable jobs to open or close availability; campaign sends and reminder nudges should check window state before delivering links.
- Embedding uses a lightweight widget/container that respects the same rendering pipeline as the hosted URL, with options to pass host context (e.g., account/contact) or force anonymous mode.

## Analytics and storage
- Responses persist as a survey response record with completion state and timestamps plus per-question answers; branching metadata is recorded to understand which questions were shown and skipped.
- Response and completion rates derive from counts of delivered invitations vs started vs completed responses, so campaign send logs and survey response records should align.
- Analysis views should reuse stored answers with aggregation helpers (choice counts, average ratings, text exports) rather than recalculating from raw events, enabling consistent reporting across campaigns, embeds, and direct links.
