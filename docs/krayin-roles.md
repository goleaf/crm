# Krayin Roles

Source: [Krayin User Documentation - Roles](https://docs.krayincrm.com/2.x/settings/role.html)

## Overview
- Roles bundle permissions and access levels for users.
- Two permission modes: **Custom** (granular, assign specific abilities) and **All** (superuser-style full access).
- Filters in the roles grid support Id, Name, and Permission Type.

## Create a role (Krayin flow)
1. Go to `Settings >> Roles >> Create Role`.
2. Enter Name and Description.
3. Choose Permissions:
   - **Custom**: pick only the capabilities the role needs.
   - **All**: unrestricted access (reserved for admins/superusers).
4. Save; the role appears in the grid and is available when creating users.

## Edit or delete
- Edit: rename, change description, or swap permission type.
- Delete: remove unused roles from the action menu in the grid.

## Parity notes for this project
- We already use team-scoped roles via Filament Shield + Spatie Permission (`super_admin` bypasses checks).
- Map “All” to `super_admin` or an equivalent team-level admin role; use custom permissions for everything else.
- Ensure new roles surface in user creation forms (team context) and in role filters.
