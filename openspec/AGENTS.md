# OpenSpec Guidelines

1. Place new changes under `openspec/changes/<change-id>/` with `proposal.md`, `tasks.md`, and spec deltas inside `specs/`.
2. Use verb-led, kebab-case `change-id`s (e.g., `define-company-information-management`).
3. Each spec file must include `## ADDED|MODIFIED|REMOVED Requirements` sections and at least one `#### Scenario:` per requirement.
4. Capture architectural notes in `design.md` when requirements span multiple subsystems.
5. Document tasks (including validation steps) in `tasks.md` in order.
6. If `openspec` tooling is missing, note the limitation in the change summary and proceed with manual files.
