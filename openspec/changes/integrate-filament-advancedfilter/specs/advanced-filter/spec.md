# Filament Advanced Filter Integration

## ADDED Requirements

#### Requirement 1: Provide boolean clause filters with null handling.
- Resources shall offer boolean filters that handle true/false/null (unknown) states with options to treat nulls as unknown, true, or false and to show/hide unknowns.
#### Scenario: Filter by active status with null handling
- Given a resource has an `is_active` column that can be null
- When the boolean filter is set to show unknowns and nulls are treated as unknown
- Then the filter displays unknown as a separate option and returns records accordingly

#### Requirement 2: Provide date filters with clause conditions and intervals.
- Resources shall provide date filters supporting equal/not equal, on/after, on/before, more/less than (with past/future intervals like days/weeks/months/years), between, and set/not set.
#### Scenario: Filter dates within a future interval
- Given records have a `published_at` date
- When the user filters for “more than 3 days from now”
- Then only records with `published_at` more than 3 days in the future are shown

#### Requirement 3: Provide number filters with range and set conditions.
- Resources shall provide number filters supporting equal/not equal, on/after/before, more than/less than, between, and set/not set for numeric columns.
#### Scenario: Filter by numeric range
- Given records have a `quantity` field
- When the user sets a between filter from 10 to 25
- Then only records with quantity in that range are shown

#### Requirement 4: Provide text filters with clause options.
- Resources shall offer text filters supporting equal/not equal, starts/does not start with, ends/does not end with, contains/does not contain, and set/not set.
#### Scenario: Filter by text contains
- Given records have a `name` field
- When the user filters for names that contain “pro”
- Then only records whose names contain “pro” remain visible

#### Requirement 5: Support default clauses and clause labels.
- Filters shall allow setting a default clause per filter and optionally display clause labels to clarify the chosen operator.
#### Scenario: Default to “contains” with visible label
- Given a text filter on `brand`
- When the default clause is set to contains and clause labels are enabled
- Then the filter initializes with “contains” selected and shows the operator label to the user

#### Requirement 6: Allow custom wrappers for filter fields.
- Filters shall support overriding the wrapper component (e.g., using a Group instead of Fieldset) to match resource layout needs.
#### Scenario: Use group wrapper for filters
- Given a resource prefers grouped filter layout
- When the filter wrapper is set to use a Group component
- Then the filter fields render within that wrapper while retaining full functionality

#### Requirement 7: Configure debounce for filter inputs.
- Filters shall allow setting a debounce interval (default 500ms) to control when input changes apply, adjustable per filter.
#### Scenario: Increase debounce for a text filter
- Given a text filter may receive rapid typing
- When debounce is set to 700ms
- Then filter queries wait 700ms after the last keystroke before applying

#### Requirement 8: Publish translations for filters when needed.
- Projects shall support publishing translation files for filter UI so labels/messages can be localized.
#### Scenario: Publish advanced filter translations
- Given localization is required
- When the translation tag for filament-advancedfilter is published
- Then filter labels/messages are available for localization across supported languages
