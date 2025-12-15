# Employee Directory

## ADDED Requirements

#### Requirement 1: Directory search and filtering expose employee contact and role details.
- Scenario: A user opens the employee directory, searches for "Sanchez" and filters to Department = Engineering and Status = Active; the results list shows each matching employee with photo/initials, job title, department, work email, and phone, and supports click-through into the full employee record.
- Scenario: Clearing filters returns to the full directory while preserving quick filters for department, job title, location, and employment status so users can repeatedly narrow the list without reloading.

#### Requirement 2: Department and team views group employees for browsing.
- Scenario: A department head selects the "Marketing" tab; the directory shows only marketing team members grouped by sub-team (e.g., Content, Demand Gen), displays counts per group, and allows exporting the filtered list with contact details for offline sharing.
- Scenario: When an employee's department assignment changes, the directory automatically moves them to the new department grouping and updates any saved filters or smart lists that rely on department membership.

#### Requirement 3: Reporting structure visualization highlights managers and direct reports.
- Scenario: From the directory, a user selects "Org chart" view for an employee; the system displays the employee's manager, peers, and direct reports with lines indicating reporting relationships and links to each profile.
- Scenario: Navigating upward from a direct report reveals the chain of command up to the top-level leader, ensuring reporting structures remain discoverable even when employees move between teams.
