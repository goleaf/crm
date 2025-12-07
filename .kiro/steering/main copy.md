---
inclusion: always
---
<!------------------------------------------------------------------------------------
   Add rules to this file or a short description and have Kiro refine them for you.
   
   Learn about inclusion modes: https://kiro.dev/docs/steering/#inclusion-modes
-------------------------------------------------------------------------------------> 

- When a new model should be summarized by AI (used by `RecordContextBuilder` / `RecordSummaryService`), add a `build...Context` branch for it and include core fields plus notes/tasks so it doesn’t hit “unsupported record type”.
