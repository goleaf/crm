# Workflow Calculated Fields

Status: Not Implemented

Coverage:
- Done: 0
- Partial: 1 (custom fields infrastructure could store results)
- Missing: 14+ formula/rollup capabilities from SuiteCRM list

Notes
- There is no formula builder, expression parser, or scheduled recalculation service. Workflow steps store `input_data`/`output_data`, but nothing computes fields.
- Custom fields and process payloads could host calculated values if/when an engine is added.

Source: docs/suitecrm-features.md (Workflow Calculated Fields)
