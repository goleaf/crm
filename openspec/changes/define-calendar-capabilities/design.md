# Design Notes

## Calendar and Event Model
- A unified calendar renderer consumes events from meetings, calls, tasks, and custom activities through a normalized event adapter that emits start/end, timezone, all-day flag, recurrence, attendees, and source module; this enables day/week/month/year layouts without duplicating module logic.
- Personal, shared, and team calendars resolve to scoped event collections backed by ownership and team membership; team calendars aggregate events for members with shared colors per user to keep attribution visible in merged views.
- Color palettes are assigned per calendar and optionally per activity type so that merged calendars (personal + team + shared) stay visually distinguishable; colors travel through exports and print views.

## Sharing, Permissions, and Interaction
- Calendar sharing produces ACL entries (private, busy-only, read-only, edit) per target user/group and per calendar, controlling which events appear, whether details are visible, and whether drag/drop edits or deletes are allowed; permissions apply before rendering and before accepting quick-create or reschedule actions.
- Drag-and-drop and resize interactions update start/end times through the activity adapter with conflict checks (room/resource availability when applicable) and log reschedule reasons when required; undo links or revert-on-failure keep the UI consistent.
- Quick create launches a scoped modal from any view cell, prefilling date/time based on the slot clicked and writing to the correct calendar (personal or team) while triggering the same validation pipeline as full forms.
- Filters and search operate across merged calendars with facets for owner, team, activity type, status, and keyword; results update the view and the printable/exportable slice.

## Sync, Export, and Print
- Google and Outlook connectors rely on OAuth with per-user tokens, support bidirectional sync (respecting ownership/permissions), and use delta tokens or webhooks where available to minimize drift; conflicts follow a last-write-with-notes strategy and log sync status per event.
- iCal feeds are published per calendar and per filter (e.g., team calendar, personal busy-only) with rotating tokens; inbound .ics imports respect permissions and map to the normalized event model.
- Print/export pipelines render the current filtered range in a print-friendly layout and can emit iCal or CSV slices by view (day/week/month/year) to preserve portability without exposing unauthorized events.
