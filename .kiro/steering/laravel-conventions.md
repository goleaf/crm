---
inclusion: always
---

- Base models that are meant to be extended (e.g., `ProcessDefinition` used by `WorkflowDefinition`) must not be declared `final`; keep them extendable to avoid inheritance errors.
