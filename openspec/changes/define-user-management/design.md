# Design Notes

## Identity and lifecycle
- User records carry a status (active, inactive/disabled) plus timestamps and optional reason so lifecycle changes can be audited and enforced across authentication and authorization checks.
- Admin creation sets initial access (team/role) and seeds profile basics; email/username uniqueness is enforced and invitations or temporary passwords can be issued depending on onboarding policy.

## Profile data and preferences
- Core profile fields include name, title, department, phone numbers, manager, avatar, and optional links; extended profile traits can be stored in metadata to avoid schema churn.
- Preferences (timezone, locale, notification channels/frequency, accessibility options, default landing page) are stored per user and default from tenant-level settings, with change timestamps to drive audits and cache invalidation.

## Activity tracking and audit
- Activity events include authentication attempts, status toggles, profile edits, preference updates, password resets, and impersonation sessions; each event stores actor, target user, timestamp, and source IP/device when available.
- User detail views expose an activity timeline for administrators, and a limited self-view so users can verify their own recent logins and preference changes without seeing sensitive admin actions.
