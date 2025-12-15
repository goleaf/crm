# Contact Groups

Groups let admins categorize contacts into reusable buckets (similar to Krayin’s Settings → Groups). They’re scoped per team and can be managed from the Filament admin.

## Manage groups

1. In Filament, go to **Settings → Groups**.
2. Click **Create Group**, fill in **Name** and optional **Description**, then save.
3. Use the built-in filters (ID, Name) to find groups quickly.
4. Open a group to attach/detach people via the People relation.

## Assign groups to contacts

- In the People form, select one or more groups via the **Groups** multiselect.
- The People table shows a **Groups** column (toggleable) for quick visibility.

## Data model

- `groups` table: `team_id`, `name` (unique per team), optional `description`.
- Pivot `group_people` links groups to contacts (`people`).
