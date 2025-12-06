# Implementation Plan

- [ ] 1. Create database migration for new account fields
  - Add website, industry, revenue, employee_count, description fields to companies table
  - Add indexes for duplicate detection (name, website)
  - Create account_merges table for audit trail
  - _Requirements: 1.5, 5.1_

- [ ] 2. Update Company model with new fields and methods
  - [ ] 2.1 Add new fillable fields to Company model
    - Add website, industry, revenue, employee_count, description to fillable array
    - Update casts for numeric fields
    - _Requirements: 1.1, 1.5_

  - [ ] 2.2 Write property test for account creation persistence
    - **Property 1: Account creation persistence**
    - **Validates: Requirements 1.1**

  - [ ] 2.3 Write property test for account update persistence
    - **Property 2: Account update persistence**
    - **Validates: Requirements 1.2**

  - [ ] 2.4 Add duplicate detection methods to Company model
    - Implement findPotentialDuplicates() method
    - Implement calculateSimilarityScore() method
    - _Requirements: 5.1, 5.3, 5.4, 5.5_

  - [ ] 2.5 Write property test for fuzzy name matching
    - **Property 12: Fuzzy name matching**
    - **Validates: Requirements 5.3, 5.4**

  - [ ] 2.6 Write property test for similarity score calculation
    - **Property 13: Similarity score calculation**
    - **Validates: Requirements 5.5**

  - [ ] 2.7 Add pipeline value calculation method
    - Implement getTotalPipelineValue() method to sum open opportunities
    - _Requirements: 7.3_

  - [ ] 2.8 Write property test for pipeline value calculation
    - **Property 19: Pipeline value calculation**
    - **Validates: Requirements 7.3**

  - [ ] 2.9 Add activity timeline method
    - Implement getActivityTimeline() method to retrieve chronologically sorted activities
    - _Requirements: 3.1, 3.5_

  - [ ] 2.10 Write property test for activity chronological ordering
    - **Property 7: Activity chronological ordering**
    - **Validates: Requirements 3.1, 3.5**

- [ ] 3. Create AccountMerge model and migration
  - [ ] 3.1 Create AccountMerge model
    - Define relationships to Company and User
    - Add casts for JSON fields
    - _Requirements: 6.3_

  - [ ] 3.2 Create factory for AccountMerge model
    - Generate test data for merge audit records
    - _Requirements: 6.3_

- [ ] 4. Implement DuplicateDetectionService
  - [ ] 4.1 Create DuplicateDetectionService class
    - Implement findDuplicates() method with name and domain matching
    - Implement calculateSimilarity() method using Levenshtein distance
    - Implement suggestMerge() method for merge recommendations
    - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5_

  - [ ] 4.2 Write property test for duplicate detection on creation
    - **Property 11: Duplicate detection on creation**
    - **Validates: Requirements 5.1, 5.2**

  - [ ] 4.3 Write unit tests for DuplicateDetectionService
    - Test edge cases: empty fields, special characters, very long names
    - Test similarity score boundaries
    - _Requirements: 5.1, 5.3, 5.4, 5.5_

- [ ] 5. Implement AccountMergeService
  - [ ] 5.1 Create AccountMergeService class
    - Implement merge() method with database transaction
    - Implement previewMerge() method for field comparison
    - Implement relationship transfer logic
    - Implement rollback() method
    - _Requirements: 6.1, 6.2, 6.3, 6.4, 6.5_

  - [ ] 5.2 Write property test for merge relationship transfer
    - **Property 15: Merge relationship transfer**
    - **Validates: Requirements 6.2**

  - [ ] 5.3 Write property test for merge data preservation
    - **Property 17: Merge data preservation**
    - **Validates: Requirements 6.4**

  - [ ] 5.4 Write property test for merge transaction rollback
    - **Property 18: Merge transaction rollback**
    - **Validates: Requirements 6.5**

  - [ ] 5.5 Write property test for merge audit trail
    - **Property 16: Merge audit trail**
    - **Validates: Requirements 6.3**

  - [ ] 5.6 Write unit tests for AccountMergeService
    - Test merging accounts with no relationships
    - Test merge preview completeness
    - Test error handling scenarios
    - _Requirements: 6.1, 6.2, 6.3, 6.4, 6.5_

- [ ] 6. Checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [ ] 7. Update CompanyResource with new fields
  - [ ] 7.1 Add new fields to form schema
    - Add website, industry, revenue, employee_count, description inputs
    - Add validation rules
    - _Requirements: 1.1, 1.5_

  - [ ] 7.2 Add new columns to table
    - Add website, industry, revenue, employee_count columns
    - Configure column visibility and sorting
    - _Requirements: 1.3_

  - [ ] 7.3 Add filters for new fields
    - Add industry filter
    - Add revenue range filter
    - Add employee count range filter
    - _Requirements: 9.2, 9.3_

  - [ ] 7.4 Write property test for filter combination
    - **Property 23: Filter combination**
    - **Validates: Requirements 9.2, 9.3**

  - [ ] 7.5 Enhance search configuration
    - Add website and phone to searchable attributes
    - Configure full-text search
    - _Requirements: 9.1, 9.5_

  - [ ] 7.6 Write property test for multi-field search
    - **Property 22: Multi-field search**
    - **Validates: Requirements 9.1, 9.5**

- [ ] 8. Create duplicate detection Filament action
  - [ ] 8.1 Create DetectDuplicatesAction
    - Integrate with DuplicateDetectionService
    - Display potential duplicates in modal
    - Show similarity scores
    - _Requirements: 5.1, 5.2, 5.5_

  - [ ] 8.2 Add action to CompanyResource table
    - Add bulk action for duplicate detection
    - Add individual record action
    - _Requirements: 5.3_

  - [ ] 8.3 Write feature test for duplicate detection action
    - Test action triggers duplicate detection
    - Test modal displays results
    - _Requirements: 5.1, 5.2, 5.3_

- [ ] 9. Create merge accounts Filament action
  - [ ] 9.1 Create MergeAccountsAction wizard
    - Step 1: Select primary account
    - Step 2: Compare fields and select values
    - Step 3: Confirm and execute merge
    - Integrate with AccountMergeService
    - _Requirements: 6.1, 6.2, 6.3_

  - [ ] 9.2 Add merge action to duplicate detection results
    - Allow merging directly from duplicate detection modal
    - _Requirements: 6.1_

  - [ ] 9.3 Write property test for merge preview completeness
    - **Property 14: Merge preview completeness**
    - **Validates: Requirements 6.1**

  - [ ] 9.4 Write feature test for merge action
    - Test wizard flow
    - Test merge execution
    - Test error handling
    - _Requirements: 6.1, 6.2, 6.3, 6.4, 6.5_

- [ ] 10. Enhance account view page with relationships
  - [ ] 10.1 Create activity timeline infolist component
    - Display notes, tasks, and opportunities chronologically
    - Show activity type, date, and summary
    - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5_

  - [ ] 10.2 Write property test for complete data retrieval
    - **Property 6: Complete data retrieval with relationships**
    - **Validates: Requirements 1.3, 2.2, 3.1, 3.2, 3.3, 4.4, 7.1, 8.1, 8.2, 8.3, 8.4**

  - [ ] 10.3 Write property test for activity linkage
    - **Property 8: Activity linkage**
    - **Validates: Requirements 3.4**

  - [ ] 10.4 Add pipeline value display
    - Show total pipeline value from open opportunities
    - Display opportunity breakdown by stage
    - _Requirements: 7.3_

  - [ ] 10.5 Add people relationship section
    - Display all associated contacts
    - Show contact details and roles
    - _Requirements: 2.2, 8.4_

  - [ ] 10.6 Write property test for bidirectional relationship consistency
    - **Property 4: Bidirectional relationship consistency**
    - **Validates: Requirements 2.1, 2.4, 2.5**

  - [ ] 10.7 Write property test for relationship deletion
    - **Property 5: Relationship deletion preserves entities**
    - **Validates: Requirements 2.3**

- [ ] 11. Implement relationship management features
  - [ ] 11.1 Add action to associate people with accounts
    - Create AssociatePeopleAction
    - Allow selecting multiple people
    - _Requirements: 2.1, 2.4_

  - [ ] 11.2 Add action to remove people associations
    - Create RemovePeopleAssociationAction
    - Confirm before removing
    - _Requirements: 2.3_

  - [ ] 11.3 Add action to create opportunities from account
    - Pre-fill company_id when creating from account context
    - _Requirements: 7.2_

  - [ ] 11.4 Write property test for opportunity auto-linking
    - **Property 20: Opportunity auto-linking**
    - **Validates: Requirements 7.2**

  - [ ] 11.5 Write property test for soft deletion preserving relationships
    - **Property 3: Soft deletion preserves relationships**
    - **Validates: Requirements 1.4**

- [ ] 12. Add opportunity filtering on account view
  - [ ] 12.1 Create opportunity filter component
    - Filter by stage
    - Filter by owner
    - Filter by date range
    - _Requirements: 7.4_

  - [ ] 12.2 Write property test for opportunity filtering
    - **Property 21: Opportunity filtering**
    - **Validates: Requirements 7.4**

- [ ] 13. Enhance export functionality
  - [ ] 13.1 Update CompanyExporter
    - Include new fields (website, industry, revenue, employee_count, description)
    - Include custom field data
    - _Requirements: 10.1, 10.3_

  - [ ] 13.2 Add Excel export format
    - Configure Excel exporter alongside CSV
    - _Requirements: 10.2_

  - [ ] 13.3 Write property test for export data completeness
    - **Property 25: Export data completeness**
    - **Validates: Requirements 10.1, 10.3, 10.4, 10.5**

  - [ ] 13.4 Write property test for export format support
    - **Property 26: Export format support**
    - **Validates: Requirements 10.2**

  - [ ] 13.5 Write unit tests for export functionality
    - Test export with filters applied
    - Test export with selected records
    - Test export with custom fields
    - _Requirements: 10.4, 10.5_

- [ ] 14. Add custom field integration tests
  - [ ] 14.1 Write property test for custom field type validation
    - **Property 9: Custom field type validation**
    - **Validates: Requirements 4.2**

  - [ ] 14.2 Write property test for custom field lifecycle
    - **Property 10: Custom field lifecycle**
    - **Validates: Requirements 4.1, 4.5**

- [ ] 15. Add sorting tests
  - [ ] 15.1 Write property test for sort order correctness
    - **Property 24: Sort order correctness**
    - **Validates: Requirements 9.4**

- [ ] 16. Final checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [ ] 17. Update documentation and translations
  - [ ] 17.1 Add translation keys for new fields
    - Add to lang/en/app.php
    - Add to lang/uk/app.php (if applicable)
    - _Requirements: All_

  - [ ] 17.2 Update custom field configuration
    - Add account-specific custom field types to config/custom-fields.php
    - _Requirements: 4.1, 4.3_
