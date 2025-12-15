# Implementation Plan

- [ ] 1. Set up dependencies and testing infrastructure
  - Install sabre/vobject package for vCard parsing
  - Install pestphp/pest-plugin-property for property-based testing
  - Create test data generators for contacts module
  - _Requirements: All_

- [ ] 2. Enhance PeopleResource UI with new contact features
  - [ ] 2.1 Add role management section to form
    - Add multi-select for contact roles with create option
    - Display assigned roles in table column
    - Add role filter to table
    - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.5_
  
  - [ ] 2.2 Add communication preferences section to form
    - Create communication preferences form section with channel toggles
    - Add preferred channel and time fields
    - Display communication preferences in view page
    - Add opt-out indicators in table
    - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5_
  
  - [ ] 2.3 Add persona assignment to form
    - Add persona select field with create option
    - Display persona in table column
    - Add persona filter to table
    - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5_
  
  - [ ] 2.4 Add multi-account association management
    - Create relation manager for account associations
    - Add primary account designation toggle
    - Display all associated accounts in view page
    - _Requirements: 13.1, 13.2, 13.3, 13.4, 13.5_

- [ ] 3. Implement duplicate detection UI
  - [ ] 3.1 Create duplicate detection action for PeopleResource
    - Add "Check for Duplicates" action to view page
    - Display similarity scores and matching contacts
    - Show notification when duplicates found
    - _Requirements: 7.1, 7.2, 7.3, 7.4, 7.5_
  
  - [ ] 3.2 Write property test for duplicate detection
    - **Property 17: Duplicate detection on creation**
    - **Validates: Requirements 7.1**
  
  - [ ] 3.3 Write property test for fuzzy name matching
    - **Property 18: Fuzzy name matching detects variations**
    - **Validates: Requirements 7.4, 7.5**

- [ ] 4. Implement contact merge functionality
  - [ ] 4.1 Create merge action for PeopleResource
    - Add "Merge Contacts" action with field selection UI
    - Display side-by-side comparison of contact fields
    - Show preview of relationships to be transferred
    - Implement merge confirmation modal
    - _Requirements: 8.1, 8.2, 8.3, 8.4, 8.5_
  
  - [ ] 4.2 Write property test for relationship transfer
    - **Property 19: Contact merge transfers all relationships**
    - **Validates: Requirements 8.2**
  
  - [ ] 4.3 Write property test for data preservation
    - **Property 20: Contact merge preserves unique data**
    - **Validates: Requirements 8.4**
  
  - [ ] 4.4 Write property test for merge rollback
    - **Property 21: Merge operation rollback on failure**
    - **Validates: Requirements 8.5**

- [ ] 5. Implement vCard import/export functionality
  - [ ] 5.1 Add vCard export action to PeopleResource
    - Add "Export as vCard" action for single contact
    - Add bulk action for exporting multiple contacts
    - Generate vCard 4.0 format files
    - _Requirements: 9.2, 9.4, 9.5_
  
  - [ ] 5.2 Add vCard import functionality
    - Create import action with file upload
    - Parse vCard files and create/update contacts
    - Display import results and errors
    - Handle both vCard 3.0 and 4.0 formats
    - _Requirements: 9.1, 9.3, 9.5_
  
  - [ ] 5.3 Write property test for vCard round-trip
    - **Property 22: vCard round-trip preservation**
    - **Validates: Requirements 9.1, 9.2**
  
  - [ ] 5.4 Write property test for vCard validation
    - **Property 23: vCard validation rejects invalid format**
    - **Validates: Requirements 9.3**
  
  - [ ] 5.5 Write property test for multi-contact export
    - **Property 24: Multi-contact vCard export completeness**
    - **Validates: Requirements 9.4**

- [ ] 6. Implement portal access management
  - [ ] 6.1 Create portal access grant action
    - Add "Grant Portal Access" action to PeopleResource
    - Generate secure credentials
    - Send credentials email to contact
    - Update contact portal status
    - _Requirements: 6.1_
  
  - [ ] 6.2 Create portal access revoke action
    - Add "Revoke Portal Access" action
    - Disable portal user account
    - Update contact portal status
    - _Requirements: 6.1_
  
  - [ ] 6.3 Create portal authentication guard
    - Configure portal guard in auth.php
    - Create portal login page
    - Implement portal authentication middleware
    - _Requirements: 6.2_
  
  - [ ] 6.4 Create portal dashboard page
    - Create portal dashboard with contact information
    - Display accessible cases with real-time status
    - Implement authorization checks for data access
    - Log all portal access activities
    - _Requirements: 6.2, 6.3, 6.4, 6.5_
  
  - [ ] 6.5 Write property test for portal access creation
    - **Property 13: Portal access creation**
    - **Validates: Requirements 6.1**
  
  - [ ] 6.6 Write property test for portal authentication
    - **Property 14: Portal authentication with valid credentials**
    - **Validates: Requirements 6.2**
  
  - [ ] 6.7 Write property test for portal authorization
    - **Property 15: Portal authorization restricts data access**
    - **Validates: Requirements 6.3**
  
  - [ ] 6.8 Write property test for portal activity logging
    - **Property 16: Portal activity logging**
    - **Validates: Requirements 6.5**

- [ ] 7. Enhance search and filtering capabilities
  - [ ] 7.1 Add advanced search functionality
    - Implement full-text search across all contact fields
    - Add search for custom field values
    - Add search debouncing for performance
    - _Requirements: 11.1, 11.5_
  
  - [ ] 7.2 Add comprehensive filters
    - Add role filter
    - Add persona filter
    - Add account filter
    - Add owner filter
    - Add custom field filters
    - _Requirements: 11.2, 11.3_
  
  - [ ] 7.3 Write property test for search matching
    - **Property 27: Search returns matching contacts**
    - **Validates: Requirements 11.1**
  
  - [ ] 7.4 Write property test for filter application
    - **Property 28: Filter application returns only matching contacts**
    - **Validates: Requirements 11.2**
  
  - [ ] 7.5 Write property test for sort order
    - **Property 29: Sort order correctness**
    - **Validates: Requirements 11.4**

- [ ] 8. Implement export functionality
  - [ ] 8.1 Add CSV/Excel export actions
    - Add export action to PeopleResource
    - Support CSV and Excel formats
    - Include standard and custom field data
    - Respect current filters and search criteria
    - _Requirements: 12.1, 12.2, 12.3, 12.4, 12.5_
  
  - [ ] 8.2 Write property test for export completeness
    - **Property 30: Export contains all selected contacts**
    - **Validates: Requirements 12.1, 12.4**
  
  - [ ] 8.3 Write property test for custom field inclusion
    - **Property 31: Export includes custom field data**
    - **Validates: Requirements 12.3**
  
  - [ ] 8.4 Write property test for filtered export
    - **Property 32: Filtered export respects criteria**
    - **Validates: Requirements 12.5**

- [ ] 9. Implement core contact management properties
  - [ ] 9.1 Write property test for contact persistence
    - **Property 1: Contact persistence with valid data**
    - **Validates: Requirements 1.1**
  
  - [ ] 9.2 Write property test for contact updates
    - **Property 2: Contact update preservation**
    - **Validates: Requirements 1.2**
  
  - [ ] 9.3 Write property test for soft delete
    - **Property 3: Contact soft delete preserves relationships**
    - **Validates: Requirements 1.4**
  
  - [ ] 9.4 Write property test for role assignment
    - **Property 4: Role assignment creates association**
    - **Validates: Requirements 2.1**
  
  - [ ] 9.5 Write property test for multiple roles
    - **Property 5: Multiple role assignment**
    - **Validates: Requirements 2.5**
  
  - [ ] 9.6 Write property test for role filtering
    - **Property 6: Role filtering returns only matching contacts**
    - **Validates: Requirements 2.4**
  
  - [ ] 9.7 Write property test for communication preferences
    - **Property 7: Communication preference storage and retrieval**
    - **Validates: Requirements 3.1**
  
  - [ ] 9.8 Write property test for opt-out enforcement
    - **Property 8: Opt-out prevents communication**
    - **Validates: Requirements 3.5**
  
  - [ ] 9.9 Write property test for interaction history ordering
    - **Property 9: Interaction history chronological ordering**
    - **Validates: Requirements 4.1, 4.5**
  
  - [ ] 9.10 Write property test for note/task linking
    - **Property 10: Note and task linking**
    - **Validates: Requirements 4.4**
  
  - [ ] 9.11 Write property test for persona assignment
    - **Property 11: Persona assignment and retrieval**
    - **Validates: Requirements 5.1**
  
  - [ ] 9.12 Write property test for persona filtering
    - **Property 12: Persona filtering returns only matching contacts**
    - **Validates: Requirements 5.3**
  
  - [ ] 9.13 Write property test for multi-account association
    - **Property 33: Multi-account association creation**
    - **Validates: Requirements 13.1**
  
  - [ ] 9.14 Write property test for account association removal
    - **Property 34: Account association removal preserves entities**
    - **Validates: Requirements 13.3**
  
  - [ ] 9.15 Write property test for primary account designation
    - **Property 35: Primary account designation**
    - **Validates: Requirements 13.5**

- [ ] 10. Add custom field support for personas
  - [ ] 10.1 Create persona-specific custom field associations
    - Add custom field group for each persona
    - Display persona-specific fields conditionally
    - _Requirements: 5.5, 10.1, 10.4_
  
  - [ ] 10.2 Write property test for custom field availability
    - **Property 25: Custom field availability after creation**
    - **Validates: Requirements 10.1**
  
  - [ ] 10.3 Write property test for custom field validation
    - **Property 26: Custom field type validation**
    - **Validates: Requirements 10.2**

- [ ] 11. Enhance interaction history display
  - [ ] 11.1 Improve activity timeline in view page
    - Display notes, tasks, opportunities, and cases chronologically
    - Add filtering by activity type
    - Add pagination for large timelines
    - _Requirements: 4.1, 4.2, 4.3, 4.4, 4.5_

- [ ] 12. Add performance optimizations
  - [ ] 12.1 Add database indexes
    - Add indexes on email, phone, name fields
    - Add composite indexes for multi-field searches
    - Add indexes for role and persona filtering
    - _Requirements: All (Performance)_
  
  - [ ] 12.2 Implement query optimization
    - Add eager loading for relationships in list view
    - Implement database query caching for duplicate detection
    - Optimize search queries with proper indexes
    - _Requirements: All (Performance)_

- [ ] 13. Add translations for new features
  - [ ] 13.1 Add translation keys for contact features
    - Add keys for roles, personas, communication preferences
    - Add keys for portal access labels
    - Add keys for duplicate detection and merge actions
    - Add keys for vCard import/export
    - _Requirements: All_

- [ ] 14. Create documentation
  - [ ] 14.1 Document contact management features
    - Document role management
    - Document communication preferences
    - Document persona system
    - Document duplicate detection and merging
    - Document vCard import/export
    - Document portal access
    - _Requirements: All_

- [ ] 15. Final checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.
