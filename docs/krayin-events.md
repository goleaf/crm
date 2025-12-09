# Krayin Events

Source: [Krayin User Documentation - Events](https://docs.krayincrm.com/2.x/settings/event.html)

## Overview
- Events are scheduled activities/meetings tied to leads/contacts/internal work to track milestones and follow-ups.
- Minimal fields: Name, Description, Date.
- Filters support Id and Name.

## Create an event (Krayin flow)
1. Go to `Settings >> Events >> Create Event`.
2. Fill:
   - **Name**: event title (e.g., “Product Demo”).
   - **Description**: agenda/summary.
   - **Date**: calendar picker.
3. Save; the event appears in the grid.

## Edit or delete
- Edit: update name, description, or date.
- Delete: remove from the grid via the action menu.

## Parity notes for this project
- We already have Calendar Events with richer fields (start/end, attendees, reminders). Map Krayin’s “Events” to our calendar event creation path; a “basic event” preset (name/description/date-only) could mirror Krayin’s simplicity.
- If adding a filter chip for Id/Name in our events list, align with Krayin’s filters for quick lookup.
