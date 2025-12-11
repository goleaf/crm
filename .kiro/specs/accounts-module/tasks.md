# Implementation Plan

- [x] 1. Create database migration for new account fields
  - Add website, industry, revenue, employee_count, description fields to companies table
  - Add indexes for duplicate detection (name, website)
  - Create account_merges table for audit trail
  - _Requirements: 1.5, 5.1_

- [x] 2. Update Company model with new fields and methods
  - [x] 2.1 Add new fillable fields to Company model
    - Add website, industry, revenue, employee_count, description to fillable array
    - Update casts for numeric fields
    - _Requirements: 1.1, 1.5_

  - [x] 2.2 Write property test for account creation persistence
    - **Property 1: Account creation persistence**
    - **Validates: Requirements 1.1**

  - [x] 2.3 Write property test for account update persistence
    - **Property 2: Account update persistence**
    - **Validates: Requirements 1.2**

  - [x] 2.4 Add duplicate detection methods to Company model
    - Implement findPotentialDuplicates() method
    - Implement calculateSimilarityScore() method
    - _Requirements: 5.1, 5.3, 5.4, 5.5_

  - [x] 2.5 Write property test for fuzzy name matching
    - **Property 12: Fuzzy name matching**
    - **Validates: Requirements 5.3, 5.4**

  - [x] 2.6 Write property test for similarity score calculation
    - **Property 13: Similarity score calculation**
    - **Validates: Requirements 5.5**

  - [x] 2.7 Add pipeline value calculation method
    - Implement getTotalPipelineValue() method to sum open opportunities
    - _Requirements: 7.3_

  - [x] 2.8 Write property test for pipeline value calculation
    - **Property 19: Pipeline value calculation**
    - **Validates: Requirements 7.3**

  - [x] 2.9 Add activity timeline method
    - Implement getActivityTimeline() method to retrieve chronologically sorted activities
    - _Requirements: 3.1, 3.5_

  - [x] 2.10 Write property test for activity chronological ordering
    - **Property 7: Activity chronological ordering**
    - **Validates: Requirements 3.1, 3.5**

- [x] 3. Create AccountMerge model and migration
  - [x] 3.1 Create AccountMerge model
    - Define relationships to Company and User
    - Add casts for JSON fields
    - _Requirements: 6.3_

  - [x] 3.2 Create factory for AccountMerge model
    - Generate test data for merge audit records
    - _Requirements: 6.3_

- [x] 4. Implement DuplicateDetectionService
  - [x] 4.1 Create DuplicateDetectionService class
    - Implement findDuplicates() method with name and domain matching
    - Implement calculateSimilarity() method using Levenshtein distance
    - Implement suggestMerge() method for merge recommendations
    - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5_

  - [x] 4.2 Write property test for duplicate detection on creation
    - **Property 11: Duplicate detection on creation**
    - **Validates: Requirements 5.1, 5.2**

  - [x] 4.3 Write unit tests for DuplicateDetectionService
    - Test edge cases: empty fields, special characters, very long names
    - Test similarity score boundaries
    - _Requirements: 5.1, 5.3, 5.4, 5.5_

- [x] 5. Implement AccountMergeService
  - [x] 5.1 Create AccountMergeService class
    - Implement merge() method with database transaction
    - Implement previewMerge() method for field comparison
    - Implement relationship transfer logic
    - Implement rollback() method
    - _Requirements: 6.1, 6.2, 6.3, 6.4, 6.5_

  - [x] 5.2 Write property test for merge relationship transfer
    - **Property 15: Merge relationship transfer**
    - **Validates: Requirements 6.2**

  - [x] 5.3 Write property test for merge data preservation
    - **Property 17: Merge data preservation**
    - **Validates: Requirements 6.4**

  - [x] 5.4 Write property test for merge transaction rollback
    - **Property 18: Merge transaction rollback**
    - **Validates: Requirements 6.5**

  - [x] 5.5 Write property test for merge audit trail
    - **Property 16: Merge audit trail**
    - **Validates: Requirements 6.3**

  - [x] 5.6 Write unit tests for AccountMergeService
    - Test merging accounts with no relationships
    - Test merge preview completeness
    - Test error handling scenarios
    - _Requirements: 6.1, 6.2, 6.3, 6.4, 6.5_

- [ ] 5a. Implement account type and classification features
  - [x] 5a.1 Update database migration to include account_type field
    - Add account_type enum field to companies table
    - _Requirements: 11.1_

  - [x] 5a.2 Create AccountType enum
    - Define enum values (Customer, Prospect, Partner, Competitor, Investor, Reseller)
    - Implement getLabel() and getColor() methods
    - Add translations for enum values
    - _Requirements: 11.1_

  - [x] 5a.3 Update Company model with account_type cast
    - Add account_type to fillable array
    - Add cast to AccountType enum
    - _Requirements: 11.1_

  - [x] 5a.4 Write property test for account type persistence and filtering
    - **Property 27: Account type persistence and filtering**
    - **Validates: Requirements 11.1, 11.2**

  - [x] 5a.5 Write property test for account type change audit trail
    - **Property 28: Account type change audit trail**
    - **Validates: Requirements 11.4**
    - **Test File:** `tests/Unit/Properties/AccountsModule/AccountTypeAuditTrailPropertyTest.php`
    - **Status:** ✅ Implemented 2025-12-11 (700 tests, 3150 assertions)

- [x] 5b. Implement account team collaboration features
  - [x] 5b.1 Verify AccountTeamMember model and relationships
    - Ensure model has proper relationships to Company, User, Team
    - Verify role and access_level enums exist
    - _Requirements: 12.1, 12.2_

  - [x] 5b.2 Add account team methods to Company model
    - Verify accountTeam(), accountTeamMembers(), ensureAccountOwnerOnTeam() methods
    - _Requirements: 12.1, 12.5_

  - [x] 5b.3 Write property test for account team member assignment
    - **Property 29: Account team member assignment**
    - **Validates: Requirements 12.1, 12.2**
    - **Test File:** `tests/Unit/Properties/AccountsModule/AccountTeamCollaborationPropertyTest.php`
    - **Status:** ✅ Implemented 2025-12-08 (80 tests, 353 assertions, 14.33s)

  - [x] 5b.4 Write property test for account team member removal
    - **Property 30: Account team member removal preserves history**
    - **Validates: Requirements 12.4**
    - **Test File:** `tests/Unit/Properties/AccountsModule/AccountTeamCollaborationPropertyTest.php`
    - **Status:** ✅ Implemented 2025-12-08 (included in above test file)

  - [x] 5b.5 Write property test for account owner team synchronization
    - **Property 31: Account owner team synchronization**
    - **Validates: Requirements 12.5**
    - **Test File:** `tests/Unit/Properties/AccountsModule/AccountTeamCollaborationPropertyTest.php`
    - **Status:** ✅ Implemented 2025-12-08 (included in above test file)

- [x] 5c. Implement multi-currency support
  - [x] 5c.1 Update database migration to include currency_code field
    - Add currency_code field with default 'USD'
    - _Requirements: 13.1_

  - [x] 5c.2 Update Company model with currency_code
    - Add currency_code to fillable array
    - Verify default value in attributes
    - _Requirements: 13.1_

  - [x] 5c.3 Write property test for currency code persistence
    - **Property 32: Currency code persistence**
    - **Validates: Requirements 13.1, 13.2**

- [ ] 5d. Implement social media and online presence features
  - [x] 5d.1 Update database migration to include social_links field
    - Add social_links JSON field
    - _Requirements: 14.1_

  - [x] 5d.2 Update Company model with social_links
    - Add social_links to fillable array
    - Add cast to array
    - _Requirements: 14.1_

  - [ ] 5d.3 Write property test for social media profile storage
    - **Property 33: Social media profile storage**
    - **Validates: Requirements 14.1, 14.2, 14.3**

- [-] 5e. Implement document management features
  - [x] 5e.1 Verify media library integration
    - Ensure InteractsWithMedia trait is on Company model
    - Verify attachments() relationship exists
    - Verify registerMediaCollections() method exists
    - _Requirements: 15.1, 15.2_

  - [ ] 5e.2 Write property test for document attachment lifecycle
    - **Property 34: Document attachment lifecycle**
    - **Validates: Requirements 15.1, 15.2, 15.5**

  - [x] 5e.3 Write property test for document download logging
    - **Property 35: Document download logging**
    - **Validates: Requirements 15.4**
    - **Test File:** `tests/Unit/Properties/AccountsModule/DocumentDownloadLoggingPropertyTest.php`
    - **Status:** ✅ Implemented 2025-12-11 (600 tests, 2700 assertions)

- [x] 5f. Implement account hierarchy features
  - [x] 5f.1 Update database migration to include parent_company_id field
    - Add parent_company_id foreign key
    - Add index for hierarchy queries
    - _Requirements: 16.1_

  - [x] 5f.2 Verify hierarchy methods on Company model
    - Ensure parentCompany(), childCompanies(), wouldCreateCycle() methods exist
    - _Requirements: 16.1, 16.2, 16.3_

  - [x] 5f.3 Write property test for hierarchy cycle prevention
    - **Property 36: Hierarchy cycle prevention**
    - **Validates: Requirements 16.3**

  - [x] 5f.4 Write property test for hierarchy relationship persistence
    - **Property 37: Hierarchy relationship persistence**
    - **Validates: Requirements 16.1, 16.2**

  - [x] 5f.5 Write property test for hierarchy aggregation
    - **Property 38: Hierarchy aggregation**
    - **Validates: Requirements 16.4**

- [ ] 6. Checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [ ] 7. Update CompanyResource with new fields
  - [ ] 7.1 Add new fields to form schema
    - Add website, industry, revenue, employee_count, description inputs
    - Add account_type selector (Customer, Prospect, Partner, etc.)
    - Add currency_code selector
    - Add social_links repeater (LinkedIn, Twitter, Facebook, Instagram)
    - Add parent_company_id selector with cycle validation
    - Add validation rules for all new fields
    - _Requirements: 1.1, 1.5, 11.1, 13.1, 14.1, 16.1_

  - [ ] 7.2 Add new columns to table
    - Add website, industry, revenue, employee_count columns
    - Add account_type badge column with colors
    - Add currency_code column
    - Add parent company column (if applicable)
    - Configure column visibility and sorting
    - _Requirements: 1.3, 11.5, 13.2_

  - [ ] 7.3 Add filters for new fields
    - Add industry filter
    - Add revenue range filter
    - Add employee count range filter
    - Add account_type filter
    - Add currency_code filter
    - Add hierarchy level filter (parent/child/standalone)
    - _Requirements: 9.2, 9.3, 11.2, 16.5_

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

- [ ] 13a. Implement account team UI features
  - [ ] 13a.1 Create account team relation manager
    - Display team members with roles and access levels
    - Allow adding/removing team members
    - Show account owner prominently
    - _Requirements: 12.2, 12.3_

  - [ ] 13a.2 Create AddTeamMemberAction
    - Select user from team
    - Assign role (Owner, Account Manager, Sales Rep, Support Contact, Technical Lead)
    - Assign access level (View, Edit, Manage)
    - _Requirements: 12.1_

  - [ ] 13a.3 Create RemoveTeamMemberAction
    - Prevent removing account owner
    - Confirm before removing
    - _Requirements: 12.4_

  - [ ] 13a.4 Add account team section to view page
    - Display team members in infolist
    - Show roles and access levels with badges
    - _Requirements: 12.2_

- [ ] 13b. Implement social media UI features
  - [ ] 13b.1 Create social media repeater component
    - Fields for platform (LinkedIn, Twitter, Facebook, Instagram) and URL
    - URL validation
    - _Requirements: 14.1, 14.3_

  - [ ] 13b.2 Add social media display to view page
    - Show clickable social media icons with links
    - Open links in new tab
    - _Requirements: 14.2, 14.4_

- [ ] 13c. Implement document management UI features
  - [ ] 13c.1 Create document upload action
    - File upload with validation (PDF, DOCX, XLSX, PPTX, images)
    - Show file size limits
    - _Requirements: 15.1, 15.3_

  - [ ] 13c.2 Create attachments relation manager
    - Display uploaded documents with metadata
    - Show filename, size, upload date, uploader
    - Allow download and delete actions
    - _Requirements: 15.2, 15.4, 15.5_

  - [ ] 13c.3 Add document download logging
    - Log download activity when user downloads a document
    - _Requirements: 15.4_

- [ ] 13d. Implement account hierarchy UI features
  - [ ] 13d.1 Add parent company selector to form
    - Select from existing accounts
    - Validate to prevent circular references using wouldCreateCycle()
    - _Requirements: 16.1, 16.3_

  - [ ] 13d.2 Add hierarchy display to view page
    - Show parent account (if exists)
    - Show child accounts list
    - Display hierarchy breadcrumb
    - _Requirements: 16.2_

  - [ ] 13d.3 Create child accounts relation manager
    - Display all child accounts
    - Allow navigating to child account details
    - _Requirements: 16.2_

  - [ ] 13d.4 Add hierarchy aggregation widget
    - Show aggregated data from child accounts
    - Display total opportunities, revenue, activities
    - _Requirements: 16.4_

- [ ] 13e. Implement account type UI features
  - [ ] 13e.1 Add account type to form and view
    - Display account type prominently in header
    - Show with appropriate color badge
    - _Requirements: 11.5_

  - [ ] 13e.2 Add account type change tracking
    - Log account type changes in activity history
    - _Requirements: 11.4_

- [ ] 13f. Implement currency UI features
  - [ ] 13f.1 Add currency selector to form
    - Dropdown with common currencies (USD, EUR, GBP, JPY, CAD, AUD, etc.)
    - _Requirements: 13.1_

  - [ ] 13f.2 Display currency in financial data
    - Show currency symbol/code with amounts
    - Display pipeline value in account currency
    - _Requirements: 13.2_

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

## Financial Accounting Implementation

- [ ] 18. Create financial accounting database schema
  - [ ] 18.1 Create financial_accounts table migration
    - Add id, team_id, account_code, name, category, account_type, description fields
    - Add parent_account_id for hierarchy, is_active, opening_balance, currency_code
    - Add unique constraint on account_code, indexes for performance
    - _Requirements: 17.1, 17.2, 17.3_

  - [ ] 18.2 Create journal_entries table migration
    - Add id, team_id, entry_number, transaction_date, description, reference fields
    - Add status, entry_type, created_by, posted_by, posted_at, fiscal_year_id
    - Add unique constraint on entry_number, indexes for performance
    - _Requirements: 18.1, 18.4_

  - [ ] 18.3 Create journal_entry_lines table migration
    - Add id, team_id, journal_entry_id, financial_account_id, description fields
    - Add debit_credit, amount, currency_code, exchange_rate, base_currency_amount
    - Add indexes for performance
    - _Requirements: 18.2, 19.1, 19.2, 19.3_

  - [ ] 18.4 Create general_ledger_entries table migration
    - Add id, team_id, financial_account_id, journal_entry_id, journal_entry_line_id
    - Add transaction_date, description, debit_credit, amount, currency_code
    - Add base_currency_amount, running_balance fields
    - Add indexes for performance
    - _Requirements: 18.5, 20.1, 20.2, 20.5_

  - [ ] 18.5 Create exchange_rates table migration
    - Add id, from_currency, to_currency, rate_date, rate, source fields
    - Add unique constraint and indexes
    - _Requirements: 19.1, 19.5_

  - [ ] 18.6 Create fiscal_years table migration
    - Add id, team_id, name, start_date, end_date, is_closed fields
    - Add indexes for performance
    - _Requirements: 25.1, 25.2, 25.3_

  - [ ] 18.7 Create financial_audit_logs table migration
    - Add id, team_id, auditable_type, auditable_id, event fields
    - Add old_values, new_values, user_id, ip_address, user_agent fields
    - Add indexes for performance
    - _Requirements: 22.1, 22.2, 22.3_

- [ ] 19. Create financial accounting models
  - [ ] 19.1 Create FinancialAccount model
    - Add HasTeam, SoftDeletes, HasAuditTrail traits
    - Define relationships (parentAccount, childAccounts, generalLedgerEntries)
    - Implement balance calculation methods (getCurrentBalance, getBalanceAsOf)
    - Implement validation methods (isValidAccountCode, canBeDeleted, wouldCreateCycle)
    - Add AccountCategory and AccountType enums with casts
    - _Requirements: 17.1, 17.2, 17.3, 17.4, 17.5_

  - [ ] 19.2 Write property test for financial account creation
    - **Property 39: Financial account creation with required fields**
    - **Validates: Requirements 17.1**

  - [ ] 19.3 Write property test for account code uniqueness
    - **Property 40: Account code uniqueness**
    - **Validates: Requirements 17.2**

  - [ ] 19.4 Write property test for financial account hierarchy
    - **Property 41: Financial account hierarchy**
    - **Validates: Requirements 17.3**

  - [ ] 19.5 Write property test for account deletion protection
    - **Property 43: Account deletion protection**
    - **Validates: Requirements 17.5**

  - [ ] 19.6 Create JournalEntry model
    - Add HasTeam, SoftDeletes, HasAuditTrail traits
    - Define relationships (journalEntryLines, createdBy, fiscalYear)
    - Implement validation methods (isBalanced, getTotalDebits, getTotalCredits)
    - Implement processing methods (post, reverse, canBeEdited, canBeDeleted)
    - Add JournalEntryStatus and JournalEntryType enums with casts
    - _Requirements: 18.1, 18.3, 18.4, 18.5_

  - [ ] 19.7 Write property test for journal entry validation
    - **Property 44: Journal entry validation**
    - **Validates: Requirements 18.1**

  - [ ] 19.8 Write property test for double-entry balance validation
    - **Property 46: Double-entry balance validation**
    - **Validates: Requirements 18.3**

  - [ ] 19.9 Write property test for sequential journal entry numbering
    - **Property 47: Sequential journal entry numbering**
    - **Validates: Requirements 18.4**

  - [ ] 19.10 Create JournalEntryLine model
    - Add HasTeam, HasAuditTrail traits
    - Define relationships (journalEntry, financialAccount)
    - Implement currency methods (getAmountInBaseCurrency, getExchangeRate)
    - Implement validation methods (isDebit, isCredit)
    - Add DebitCreditType enum with cast
    - _Requirements: 18.2, 19.1, 19.2, 19.3_

  - [ ] 19.11 Write property test for journal entry line validation
    - **Property 45: Journal entry line validation**
    - **Validates: Requirements 18.2**

  - [ ] 19.12 Write property test for multi-currency transaction validation
    - **Property 49: Multi-currency transaction validation**
    - **Validates: Requirements 19.1**

  - [ ] 19.13 Write property test for currency conversion calculation
    - **Property 50: Currency conversion calculation**
    - **Validates: Requirements 19.2**

  - [ ] 19.14 Write property test for dual currency storage
    - **Property 51: Dual currency storage**
    - **Validates: Requirements 19.3**

  - [ ] 19.15 Create GeneralLedgerEntry model
    - Add HasTeam, HasAuditTrail traits
    - Define relationships (financialAccount, journalEntry, journalEntryLine)
    - Implement balance methods (getRunningBalance, updateRunningBalance)
    - Implement currency methods (getAmountInBaseCurrency, getOriginalCurrencyAmount)
    - _Requirements: 18.5, 20.1, 20.2, 20.3, 20.5_

  - [ ] 19.16 Create ExchangeRate model
    - Define validation for currency codes and rates
    - Add methods for rate lookup and updates
    - _Requirements: 19.1, 19.5_

  - [ ] 19.17 Create FiscalYear model
    - Add HasTeam trait
    - Define validation for date ranges
    - Implement period status methods (isOpen, isClosed)
    - _Requirements: 25.1, 25.2, 25.3_

  - [ ] 19.18 Create FinancialAuditLog model
    - Add HasTeam trait
    - Define polymorphic relationships
    - Implement immutability protection
    - _Requirements: 22.1, 22.2, 22.3, 22.5_

- [ ] 20. Create financial accounting enums
  - [ ] 20.1 Create AccountCategory enum
    - Define values (Assets, Liabilities, Equity, Revenue, Expenses)
    - Implement getLabel() and getColor() methods
    - Add translations
    - _Requirements: 17.1_

  - [ ] 20.2 Create AccountType enum
    - Define values (Current Assets, Fixed Assets, Current Liabilities, etc.)
    - Implement getLabel() and getColor() methods
    - Add translations
    - _Requirements: 17.1_

  - [ ] 20.3 Create JournalEntryStatus enum
    - Define values (Draft, Posted, Reversed)
    - Implement getLabel() and getColor() methods
    - Add translations
    - _Requirements: 18.1_

  - [ ] 20.4 Create JournalEntryType enum
    - Define values (Manual, Automatic, Adjustment, Closing)
    - Implement getLabel() and getColor() methods
    - Add translations
    - _Requirements: 18.1_

  - [ ] 20.5 Create DebitCreditType enum
    - Define values (Debit, Credit)
    - Implement getLabel() and getColor() methods
    - Add translations
    - _Requirements: 18.2_

- [ ] 21. Create financial accounting services
  - [ ] 21.1 Create JournalEntryService
    - Implement createEntry() method with validation
    - Implement validateBalance() method for debit/credit validation
    - Implement postEntry() method with general ledger updates
    - Implement reverseEntry() method for corrections
    - Implement getNextEntryNumber() for sequential numbering
    - Implement createFromInvoice() and createFromPayment() for automation
    - _Requirements: 18.1, 18.3, 18.4, 18.5, 23.1, 23.2_

  - [ ] 21.2 Write property test for general ledger balance updates
    - **Property 48: General ledger balance updates**
    - **Validates: Requirements 18.5**

  - [ ] 21.3 Write property test for automatic invoice journal entries
    - **Property 55: Automatic invoice journal entries**
    - **Validates: Requirements 23.1**

  - [ ] 21.4 Write property test for automatic payment journal entries
    - **Property 56: Automatic payment journal entries**
    - **Validates: Requirements 23.2**

  - [ ] 21.5 Create GeneralLedgerService
    - Implement updateAccountBalance() method
    - Implement getAccountBalance() method with date filtering
    - Implement getTrialBalance() method
    - Implement getAccountActivity() method for ledger display
    - _Requirements: 18.5, 20.1, 20.2, 20.5, 21.1, 21.2, 21.3_

  - [ ] 21.6 Write property test for trial balance generation
    - **Property 52: Trial balance generation**
    - **Validates: Requirements 21.1, 21.2, 21.3**

  - [ ] 21.7 Create FinancialReportService
    - Implement generateBalanceSheet() method
    - Implement generateIncomeStatement() method
    - Implement generateCashFlowStatement() method
    - Implement generateTrialBalance() method
    - Implement generateComparativeReport() method
    - _Requirements: 24.1, 24.2, 24.3, 24.4_

  - [ ] 21.8 Write property test for financial report generation
    - **Property 57: Financial report generation**
    - **Validates: Requirements 24.1**

  - [ ] 21.9 Create MultiCurrencyService
    - Implement convertAmount() method with exchange rate lookup
    - Implement getExchangeRate() method
    - Implement updateExchangeRates() method for external API integration
    - Implement getBaseCurrency() method
    - _Requirements: 19.1, 19.2, 19.4, 19.5_

  - [ ] 21.10 Create FinancialAuditService
    - Implement audit trail creation for all financial data changes
    - Implement audit trail querying and filtering
    - Implement immutability protection
    - _Requirements: 22.1, 22.2, 22.3, 22.4, 22.5_

  - [ ] 21.11 Write property test for financial audit trail creation
    - **Property 53: Financial audit trail creation**
    - **Validates: Requirements 22.1**

  - [ ] 21.12 Write property test for audit trail immutability
    - **Property 54: Audit trail immutability**
    - **Validates: Requirements 22.5**

- [ ] 22. Create financial accounting Filament resources
  - [ ] 22.1 Create FinancialAccountResource
    - Form with account_code, name, category, account_type, parent_account fields
    - Table with hierarchical display and category grouping
    - Filters for category, account_type, is_active
    - Actions for creating sub-accounts
    - Validation to prevent deletion of accounts with transactions
    - _Requirements: 17.1, 17.2, 17.3, 17.4, 17.5_

  - [ ] 22.2 Write property test for chart of accounts organization
    - **Property 42: Chart of accounts organization**
    - **Validates: Requirements 17.4**

  - [ ] 22.3 Create JournalEntryResource
    - Form with transaction_date, description, reference fields
    - Repeater for journal entry lines with account, amount, debit_credit
    - Real-time balance validation (debits must equal credits)
    - Status management (Draft, Posted, Reversed)
    - Bulk actions for posting multiple entries
    - _Requirements: 18.1, 18.2, 18.3, 18.4_

  - [ ] 22.4 Create GeneralLedgerResource (read-only)
    - Display all transactions organized by account
    - Filters for account, date range, transaction type
    - Running balance calculation and display
    - Drill-down to journal entry details
    - _Requirements: 20.1, 20.2, 20.3, 20.4, 20.5_

  - [ ] 22.5 Create TrialBalanceResource (read-only)
    - Generate trial balance for selected date/period
    - Display accounts with debit/credit balances
    - Validate and highlight imbalances
    - Export to Excel/PDF functionality
    - _Requirements: 21.1, 21.2, 21.3, 21.4, 21.5_

  - [ ] 22.6 Create FinancialReportsResource
    - Generate Balance Sheet, Income Statement, Cash Flow Statement
    - Period selection (monthly, quarterly, yearly)
    - Comparative reporting (current vs previous periods)
    - Drill-down capability to underlying transactions
    - Export functionality (Excel, PDF, CSV)
    - _Requirements: 24.1, 24.2, 24.3, 24.4, 24.5_

  - [ ] 22.7 Create ExchangeRateResource
    - Form for manual exchange rate entry
    - Table displaying current rates
    - Actions for updating rates from external sources
    - Historical rate tracking
    - _Requirements: 19.5_

  - [ ] 22.8 Create FiscalYearResource
    - Form for fiscal year setup with start/end dates
    - Support for calendar and non-calendar years
    - Period closing functionality
    - Year-end rollover procedures
    - _Requirements: 25.1, 25.2, 25.3, 25.4, 25.5_

  - [ ] 22.9 Write property test for fiscal year configuration
    - **Property 58: Fiscal year configuration**
    - **Validates: Requirements 25.1, 25.2**

  - [ ] 22.10 Write property test for closed period transaction prevention
    - **Property 59: Closed period transaction prevention**
    - **Validates: Requirements 25.3**

  - [ ] 22.11 Create FinancialAuditLogResource (read-only)
    - Display all financial data changes
    - Filters for user, date range, change type
    - Detailed change tracking with old/new values
    - Export functionality for compliance
    - _Requirements: 22.1, 22.2, 22.3, 22.4_

- [ ] 23. Implement financial access control and security
  - [ ] 23.1 Create financial permissions
    - Define permissions for financial accounts, journal entries, reports
    - Create roles (Accountant, Financial Manager, CFO, Auditor)
    - Implement permission levels (View, Create, Edit, Delete, Post)
    - _Requirements: 26.1, 26.2_

  - [ ] 23.2 Write property test for financial access control
    - **Property 60: Financial access control**
    - **Validates: Requirements 26.1, 26.2**

  - [ ] 23.3 Implement enhanced security for sensitive operations
    - Require additional authentication for posting journal entries
    - Require approval for large transactions
    - Implement session logging for financial operations
    - _Requirements: 26.3, 26.5_

  - [ ] 23.4 Configure data encryption
    - Encrypt sensitive financial data at rest
    - Ensure secure transmission of financial data
    - _Requirements: 26.4_

- [ ] 24. Create financial integration with existing modules
  - [ ] 24.1 Integrate with Invoice module
    - Automatically create journal entries when invoices are created
    - Map invoice line items to revenue accounts
    - Handle tax accounting
    - _Requirements: 23.1, 23.3_

  - [ ] 24.2 Integrate with Payment module
    - Automatically create journal entries when payments are received
    - Handle partial payments and allocations
    - Update accounts receivable balances
    - _Requirements: 23.2, 23.4_

  - [ ] 24.3 Maintain integration traceability
    - Link invoices, payments, and journal entries
    - Provide audit trail for all integrations
    - _Requirements: 23.5_

- [ ] 25. Checkpoint - Ensure all financial tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [ ] 26. Create financial accounting factories and seeders
  - [ ] 26.1 Create FinancialAccount factory
    - Generate realistic chart of accounts structure
    - Include various account categories and types
    - Create parent-child relationships
    - _Requirements: 17.1, 17.3_

  - [ ] 26.2 Create JournalEntry factory
    - Generate balanced journal entries
    - Include various transaction types
    - Create realistic business transactions
    - _Requirements: 18.1, 18.2, 18.3_

  - [ ] 26.3 Create ExchangeRate factory
    - Generate realistic exchange rates
    - Include historical rate data
    - _Requirements: 19.5_

  - [ ] 26.4 Create FiscalYear factory
    - Generate fiscal years for testing
    - Include both calendar and non-calendar years
    - _Requirements: 25.1, 25.2_

  - [ ] 26.5 Create financial accounting seeder
    - Seed basic chart of accounts
    - Seed sample transactions
    - Seed exchange rates
    - Seed fiscal years
    - _Requirements: All financial requirements_

- [ ] 27. Add financial translations and documentation
  - [ ] 27.1 Add financial translation keys
    - Add to lang/en/app.php for all financial terms
    - Add to lang/uk/app.php (if applicable)
    - Include accounting terminology translations
    - _Requirements: All financial requirements_

  - [ ] 27.2 Create financial documentation
    - Document chart of accounts setup
    - Document journal entry procedures
    - Document financial reporting processes
    - Document multi-currency handling
    - _Requirements: All financial requirements_

- [ ] 28. Final checkpoint - Complete system integration test
  - Ensure all tests pass, ask the user if questions arise.
  - Test complete workflow: create accounts → record transactions → generate reports
  - Test integration between CRM and financial modules
  - Verify all property-based tests pass with 100+ iterations
