# Implementation Plan: Products & Inventory Management

- [x] 1. Database schema and migrations
  - Create migrations for all product-related tables
  - Add indexes for performance optimization
  - Set up foreign key constraints
  - _Requirements: 1.1, 2.1, 3.1, 4.1, 5.1_

- [-] 2. Core product model enhancements
  - [x] 2.1 Enhance Product model with missing relationships
    - Add product relationships (bundles, cross-sell, upsell)
    - Implement lifecycle status management
    - Add inventory calculation methods
    - _Requirements: 1.1, 1.2, 8.1, 8.2, 8.3, 9.1_

  - [ ] 2.2 Write property test for product creation
    - **Property 1: Product creation captures all required fields**
    - **Validates: Requirements 1.1**

  - [ ] 2.3 Write property test for pricing persistence
    - **Property 2: Pricing data persistence**
    - **Validates: Requirements 1.2**

- [x] 3. Category hierarchy implementation
  - [x] 3.1 Enhance ProductCategory model with hierarchy support
    - Add parent_id and sort_order fields
    - Implement parent/children relationships
    - Add methods for ancestor/descendant retrieval
    - _Requirements: 2.1, 2.2_

  - [x] 3.2 Write property test for category hierarchy
    - **Property 6: Category hierarchy preservation**
    - **Validates: Requirements 2.2**

  - [x] 3.3 Write property test for category filtering
    - **Property 7: Category filtering includes subcategories**
    - **Validates: Requirements 2.4**

  - [x] 3.4 Write property test for category sort order
    - **Property 8: Category sort order persistence**
    - **Validates: Requirements 2.5**

- [x] 4. Product attributes system
  - [x] 4.1 Enhance ProductAttribute and related models
    - Add data type validation
    - Implement attribute value management
    - Create attribute assignment logic
    - _Requirements: 3.1, 3.2, 3.3_

  - [ ] 4.2 Write property test for attribute validation
    - **Property 9: Attribute data type validation**
    - **Validates: Requirements 3.4**

  - [x] 4.3 Write property test for attribute assignment
    - **Property 10: Attribute assignment completeness**
    - **Validates: Requirements 3.5**

- [-] 5. Product variations system
  - [x] 5.1 Implement variation generation service
    - Create VariationService class
    - Implement cartesian product algorithm for variation generation
    - Add variation CRUD operations
    - _Requirements: 4.1, 4.2, 4.3_

  - [x] 5.2 Write property test for variation generation
    - **Property 11: Variation generation completeness**
    - **Validates: Requirements 4.2**

  - [ ] 5.3 Write property test for variation independence
    - **Property 12: Variation data independence**
    - **Validates: Requirements 4.3**

  - [x] 5.4 Write property test for soft delete
    - **Property 13: Soft delete preservation**
    - **Validates: Requirements 4.5**

- [x] 6. Inventory management system
  - [x] 6.1 Create InventoryService
    - Implement inventory adjustment methods
    - Add inventory calculation logic (available = current - reserved)
    - Create low-stock notification system
    - Add audit trail for inventory changes
    - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5_

  - [x] 6.2 Write property test for inventory audit trail
    - **Property 14: Inventory adjustment audit trail**
    - **Validates: Requirements 5.2**

  - [x] 6.3 Write property test for available inventory calculation
    - **Property 15: Available inventory calculation**
    - **Validates: Requirements 5.4**

  - [x] 6.4 Write property test for automatic inventory decrement
    - **Property 16: Automatic inventory decrement**
    - **Validates: Requirements 5.5**

- [x] 7. Custom fields integration
  - [x] 7.1 Integrate existing custom fields system with products
    - Add custom field support to Product model
    - Implement custom field validation
    - Add custom fields to search indexing
    - _Requirements: 6.1, 6.3, 6.4_

  - [x] 7.2 Write property test for custom field validation
    - **Property 17: Custom field validation**
    - **Validates: Requirements 6.3**

- [-] 8. Search and filtering system
  - [x] 8.1 Implement comprehensive product search
    - Add search across name, SKU, description, categories, custom fields
    - Implement category filtering with subcategory inclusion
    - Add attribute-based filtering
    - Add status filtering
    - Implement sorting by multiple fields
    - _Requirements: 7.1, 7.2, 7.3, 7.4, 7.5_

  - [-] 8.2 Write property test for search completeness
    - **Property 18: Search scope completeness**
    - **Validates: Requirements 7.1, 6.4**

  - [x] 8.3 Write property test for category filter accuracy
    - **Property 19: Category filter accuracy**
    - **Validates: Requirements 7.2**

  - [ ] 8.4 Write property test for sort order
    - **Property 20: Sort order correctness**
    - **Validates: Requirements 7.5**

- [-] 9. Product relationships
  - [ ] 9.1 Implement product relationship system
    - Create database tables for relationships (bundles, cross-sell, upsell)
    - Add relationship methods to Product model
    - Implement bundle pricing logic
    - Add upsell price validation
    - _Requirements: 8.1, 8.2, 8.3, 8.4_

  - [ ] 9.2 Write property test for upsell constraint
    - **Property 21: Upsell value constraint**
    - **Validates: Requirements 8.3**

  - [ ] 9.3 Write property test for relationship retrieval
    - **Property 22: Product relationship bidirectionality**
    - **Validates: Requirements 8.4**

- [ ] 10. Product lifecycle management
  - [ ] 10.1 Implement lifecycle status system
    - Add status enum (active, inactive, discontinued, draft)
    - Implement status change validation
    - Add business rules for inactive/discontinued products
    - Create status change audit logging
    - _Requirements: 9.1, 9.2, 9.3, 9.4, 9.5_

  - [ ] 10.2 Write property test for sales prevention
    - **Property 23: Inactive product sales prevention**
    - **Validates: Requirements 9.2, 9.3**

  - [ ] 10.3 Write property test for status audit
    - **Property 24: Status change audit**
    - **Validates: Requirements 9.5**

- [ ] 11. Import and export functionality
  - [ ] 11.1 Implement product import system
    - Create import validation logic
    - Implement CSV/Excel parsing
    - Add error reporting with row/field details
    - Handle product creation and updates
    - Support image import via SKU matching
    - _Requirements: 10.2, 10.3, 10.4_

  - [ ] 11.2 Implement product export system
    - Create export service with all fields
    - Support CSV and Excel formats
    - Implement filtered export
    - Include custom fields in export
    - _Requirements: 10.1, 10.5, 6.5_

  - [ ] 11.3 Write property test for export completeness
    - **Property 25: Export completeness**
    - **Validates: Requirements 10.1, 6.5**

  - [ ] 11.4 Write property test for import validation
    - **Property 26: Import validation error reporting**
    - **Validates: Requirements 10.3**

  - [ ] 11.5 Write property test for filtered export
    - **Property 27: Filtered export accuracy**
    - **Validates: Requirements 10.5**

- [ ] 12. Filament resources and UI
  - [ ] 12.1 Enhance ProductResource
    - Add image upload with Spatie Media Library
    - Implement category selection with hierarchy display
    - Add attribute assignment interface
    - Create variation management interface
    - Add inventory tracking UI
    - Implement lifecycle status management
    - Add translations for all labels and actions
    - _Requirements: 1.3, 1.4, 1.5, 3.3, 4.1, 5.1, 9.1_

  - [ ] 12.2 Enhance ProductCategoryResource
    - Add parent category selection
    - Implement sort order management
    - Add drag-and-drop reordering
    - Add translations
    - _Requirements: 2.1, 2.5_

  - [ ] 12.3 Create/enhance ProductAttributeResource
    - Add attribute type selection
    - Implement value management for select types
    - Add configurable/filterable/required flags
    - Add translations
    - _Requirements: 3.1, 3.2_

  - [ ] 12.4 Create relation managers
    - VariationsRelationManager for product variations
    - AttributeAssignmentsRelationManager for product attributes
    - CategoriesRelationManager for product categories
    - RelatedProductsRelationManager for bundles/cross-sell/upsell
    - _Requirements: 4.4, 3.5, 1.4, 8.4_

- [ ] 13. Service layer implementation
  - [ ] 13.1 Create ProductService
    - Implement product CRUD with business logic
    - Add slug generation
    - Implement status transition methods
    - Add product activation/deactivation
    - _Requirements: 1.1, 9.1, 9.2_

  - [ ] 13.2 Finalize VariationService
    - Add variation generation UI integration
    - Implement variation bulk operations
    - Add variation status management
    - _Requirements: 4.2, 4.5_

  - [ ] 13.3 Finalize InventoryService
    - Add low-stock notification triggers
    - Implement inventory reservation system
    - Add inventory adjustment API
    - _Requirements: 5.3, 5.4, 5.5_

- [ ] 14. Authorization and policies
  - [ ] 14.1 Implement product policies
    - Create ProductPolicy with team-based authorization
    - Add variation access control
    - Implement category management permissions
    - Add attribute management permissions
    - _Requirements: All requirements (authorization applies to all)_

  - [ ] 14.2 Write unit tests for policies
    - Test team-based access control
    - Test CRUD permissions
    - Test variation access
    - Test category/attribute permissions

- [ ] 15. Image management
  - [ ] 15.1 Implement product image handling
    - Configure Spatie Media Library collections
    - Add image upload validation
    - Implement thumbnail generation
    - Add primary image designation
    - Support multiple images with ordering
    - _Requirements: 1.3_

  - [ ] 15.2 Write property test for image ordering
    - **Property 3: Image ordering preservation**
    - **Validates: Requirements 1.3**

- [ ] 16. Checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [ ] 17. Translation support
  - [ ] 17.1 Add translation keys
    - Add all product-related labels to lang files
    - Add category labels
    - Add attribute labels
    - Add action labels
    - Add validation messages
    - _Requirements: All requirements (UI text)_

- [ ] 18. Documentation
  - [ ] 18.1 Create user documentation
    - Product management guide
    - Category hierarchy guide
    - Attribute configuration guide
    - Variation management guide
    - Inventory tracking guide
    - Import/export guide

  - [ ] 18.2 Create developer documentation
    - API documentation
    - Service layer documentation
    - Model relationship documentation
    - Extension points documentation

- [ ] 19. Performance optimization
  - [ ] 19.1 Add database indexes
    - Index frequently queried fields
    - Add composite indexes for team scoping
    - Index foreign keys
    - _Requirements: All requirements (performance)_

  - [ ] 19.2 Implement caching
    - Cache category trees
    - Cache product attributes
    - Cache available inventory
    - Implement cache invalidation
    - _Requirements: 2.2, 3.5, 5.4_

  - [ ] 19.3 Write performance tests
    - Test large product catalogs (10,000+ products)
    - Test complex category hierarchies
    - Test variation generation performance
    - Test search performance

- [ ] 20. Final checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.
