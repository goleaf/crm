# Design: Products & Inventory Management

## Overview

The Products & Inventory Management system provides comprehensive product catalog management with support for hierarchical categories, configurable attributes, product variations, and real-time inventory tracking. The system is built on Laravel with Filament v4 for the admin interface, leveraging existing patterns from the CRM codebase including team-based tenancy, activity logging, and media management.

The architecture follows a domain-driven approach with clear separation between product catalog management, attribute configuration, variation handling, and inventory tracking. All components integrate with the existing team-based multi-tenancy system and respect authorization policies.

## Architecture

### System Components

```
┌─────────────────────────────────────────────────────────────┐
│                     Filament Admin UI                        │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐      │
│  │   Product    │  │   Category   │  │  Attribute   │      │
│  │   Resource   │  │   Resource   │  │   Resource   │      │
│  └──────────────┘  └──────────────┘  └──────────────┘      │
└─────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│                    Application Layer                         │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐      │
│  │   Product    │  │  Inventory   │  │  Variation   │      │
│  │   Service    │  │   Service    │  │   Service    │      │
│  └──────────────┘  └──────────────┘  └──────────────┘      │
└─────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│                      Domain Models                           │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐      │
│  │   Product    │  │ProductCategory│ │ProductAttribute│     │
│  │              │  │              │  │              │      │
│  │ - variations │  │ - parent_id  │  │ - values     │      │
│  │ - attributes │  │ - children   │  │ - data_type  │      │
│  │ - categories │  │ - products   │  │              │      │
│  └──────────────┘  └──────────────┘  └──────────────┘      │
│                                                               │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐      │
│  │ProductVariation│ │AttributeValue│ │AttributeAssign│     │
│  │              │  │              │  │              │      │
│  │ - product_id │  │ - attribute_id│ │ - product_id │      │
│  │ - options    │  │ - value      │  │ - attribute_id│     │
│  │ - inventory  │  │              │  │ - value      │      │
│  └──────────────┘  └──────────────┘  └──────────────┘      │
└─────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│                    Infrastructure                            │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐      │
│  │   Database   │  │ Media Library│  │  Activity    │      │
│  │   (MySQL)    │  │   (Spatie)   │  │     Log      │      │
│  └──────────────┘  └──────────────┘  └──────────────┘      │
└─────────────────────────────────────────────────────────────┘
```

### Key Architectural Decisions

1. **Team-Based Tenancy**: All product data is scoped to teams using the existing `HasTeam` trait
2. **Soft Deletes**: Products, variations, and attributes use soft deletes to preserve historical data
3. **Media Management**: Product images use Spatie Media Library with automatic thumbnail generation
4. **Polymorphic Relationships**: Custom fields leverage the existing custom fields system
5. **Activity Logging**: Product changes are tracked using the existing activity log system
6. **Slug Generation**: Automatic slug generation for products and attributes with uniqueness per team

## Components and Interfaces

### Product Model

**Responsibilities:**
- Store core product information (name, SKU, description, pricing)
- Manage product status and lifecycle
- Track inventory when enabled
- Associate with categories, attributes, and variations
- Handle media attachments

**Key Methods:**
```php
public function categories(): BelongsToMany
public function variations(): HasMany
public function attributeAssignments(): HasMany
public function configurableAttributes(): BelongsToMany
public function availableInventory(): int
public function hasVariants(): bool
public function registerMediaCollections(): void
```

### ProductCategory Model

**Responsibilities:**
- Organize products hierarchically
- Support parent-child relationships
- Enable category-based filtering

**Key Methods:**
```php
public function parent(): BelongsTo
public function children(): HasMany
public function products(): BelongsToMany
public function allProducts(): Collection // includes subcategory products
```

### ProductAttribute Model

**Responsibilities:**
- Define configurable product characteristics
- Store attribute metadata (data type, validation rules)
- Support predefined value lists
- Enable variation generation

**Key Methods:**
```php
public function values(): HasMany
public function configurableForProducts(): BelongsToMany
public function isConfigurable(): bool
public function isFilterable(): bool
```

### ProductVariation Model

**Responsibilities:**
- Represent specific product variants
- Store variation-specific pricing and inventory
- Maintain attribute value combinations

**Key Methods:**
```php
public function product(): BelongsTo
public function getOptionValue(string $attributeSlug): ?string
public function availableInventory(): int
```

### ProductAttributeValue Model

**Responsibilities:**
- Store predefined values for select-type attributes
- Enable consistent attribute value selection

**Key Methods:**
```php
public function attribute(): BelongsTo
```

### ProductAttributeAssignment Model

**Responsibilities:**
- Link products with attribute values
- Store product-specific attribute data

**Key Methods:**
```php
public function product(): BelongsTo
public function attribute(): BelongsTo
public function attributeValue(): BelongsTo
```

### Service Layer

#### ProductService

**Responsibilities:**
- Orchestrate product creation and updates
- Handle slug generation and validation
- Manage product lifecycle transitions
- Coordinate with inventory service

**Key Methods:**
```php
public function createProduct(array $data): Product
public function updateProduct(Product $product, array $data): Product
public function activateProduct(Product $product): void
public function deactivateProduct(Product $product): void
public function discontinueProduct(Product $product): void
```

#### InventoryService

**Responsibilities:**
- Track inventory adjustments
- Calculate available quantities
- Trigger low-stock notifications
- Handle inventory reservations

**Key Methods:**
```php
public function adjustInventory(Product|ProductVariation $item, int $quantity, string $reason): void
public function getAvailableQuantity(Product|ProductVariation $item): int
public function reserveInventory(Product|ProductVariation $item, int $quantity): void
public function releaseInventory(Product|ProductVariation $item, int $quantity): void
public function checkLowStock(Product|ProductVariation $item): bool
```

#### VariationService

**Responsibilities:**
- Generate variations from attribute combinations
- Manage variation lifecycle
- Sync variation inventory with parent product

**Key Methods:**
```php
public function generateVariations(Product $product, array $attributeIds): Collection
public function createVariation(Product $product, array $options, array $data): ProductVariation
public function updateVariation(ProductVariation $variation, array $data): ProductVariation
public function deleteVariation(ProductVariation $variation): void
```

## Data Models

### Database Schema

#### products table
```sql
- id: bigint (PK)
- team_id: bigint (FK to teams)
- name: varchar(255)
- slug: varchar(255) unique per team
- sku: varchar(120) unique per team, nullable
- description: text, nullable
- price: decimal(10,2)
- currency_code: varchar(3) default 'USD'
- is_active: boolean default true
- track_inventory: boolean default false
- inventory_quantity: integer default 0
- custom_fields: json, nullable
- created_at: timestamp
- updated_at: timestamp
- deleted_at: timestamp, nullable
```

#### product_categories table
```sql
- id: bigint (PK)
- team_id: bigint (FK to teams)
- parent_id: bigint (FK to product_categories), nullable
- name: varchar(255)
- slug: varchar(255) unique per team
- description: text, nullable
- sort_order: integer default 0
- created_at: timestamp
- updated_at: timestamp
```

#### product_attributes table
```sql
- id: bigint (PK)
- team_id: bigint (FK to teams)
- name: varchar(255)
- slug: varchar(255) unique per team
- data_type: enum('text', 'number', 'select', 'multi_select', 'boolean')
- is_configurable: boolean default false
- is_filterable: boolean default false
- is_required: boolean default false
- description: text, nullable
- created_at: timestamp
- updated_at: timestamp
- deleted_at: timestamp, nullable
```

#### product_attribute_values table
```sql
- id: bigint (PK)
- product_attribute_id: bigint (FK to product_attributes)
- value: varchar(255)
- sort_order: integer default 0
- created_at: timestamp
- updated_at: timestamp
```

#### product_variations table
```sql
- id: bigint (PK)
- product_id: bigint (FK to products)
- name: varchar(255)
- sku: varchar(120) unique, nullable
- price: decimal(10,2), nullable
- currency_code: varchar(3)
- is_default: boolean default false
- track_inventory: boolean default false
- inventory_quantity: integer default 0
- options: json (stores attribute slug => value pairs)
- created_at: timestamp
- updated_at: timestamp
- deleted_at: timestamp, nullable
```

#### product_attribute_assignments table
```sql
- id: bigint (PK)
- product_id: bigint (FK to products)
- product_attribute_id: bigint (FK to product_attributes)
- product_attribute_value_id: bigint (FK to product_attribute_values), nullable
- value: text, nullable
- created_at: timestamp
- updated_at: timestamp
```

#### category_product table (pivot)
```sql
- product_category_id: bigint (FK to product_categories)
- product_id: bigint (FK to products)
- created_at: timestamp
- updated_at: timestamp
```

#### product_configurable_attributes table (pivot)
```sql
- product_id: bigint (FK to products)
- product_attribute_id: bigint (FK to product_attributes)
- created_at: timestamp
- updated_at: timestamp
```

### Relationships

- Product belongsToMany ProductCategory (through category_product)
- Product hasMany ProductVariation
- Product hasMany ProductAttributeAssignment
- Product belongsToMany ProductAttribute (configurable attributes)
- ProductCategory belongsTo ProductCategory (parent)
- ProductCategory hasMany ProductCategory (children)
- ProductCategory belongsToMany Product
- ProductAttribute hasMany ProductAttributeValue
- ProductAttribute belongsToMany Product (configurable for products)
- ProductVariation belongsTo Product
- ProductAttributeAssignment belongsTo Product
- ProductAttributeAssignment belongsTo ProductAttribute
- ProductAttributeAssignment belongsTo ProductAttributeValue


## Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system-essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*

### Property 1: Product creation captures all required fields
*For any* product creation request with valid data, the system should persist name, description, SKU, status, and creation metadata correctly.
**Validates: Requirements 1.1**

### Property 2: Pricing data persistence
*For any* product with pricing information, storing and retrieving the product should preserve cost price, list price, and currency exactly.
**Validates: Requirements 1.2**

### Property 3: Image ordering preservation
*For any* product with multiple images, the order of images and primary image designation should be preserved after storage and retrieval.
**Validates: Requirements 1.3**

### Property 4: Category association integrity
*For any* product assigned to categories, the category associations should persist and be retrievable bidirectionally (product→categories and category→products).
**Validates: Requirements 1.4**

### Property 5: Inventory tracking activation
*For any* product where inventory tracking is enabled, the system should initialize quantity tracking and maintain inventory state correctly.
**Validates: Requirements 1.5**

### Property 6: Category hierarchy preservation
*For any* category with parent-child relationships, the hierarchical structure should be correctly represented and retrievable.
**Validates: Requirements 2.2**

### Property 7: Category filtering includes subcategories
*For any* category filter, the results should include all products in that category and all its descendant subcategories.
**Validates: Requirements 2.4**

### Property 8: Category sort order persistence
*For any* set of categories with assigned sort orders, reordering and persisting should maintain the new sequence correctly.
**Validates: Requirements 2.5**

### Property 9: Attribute data type validation
*For any* attribute value entry, the system should validate the value against the attribute's defined data type and reject invalid values.
**Validates: Requirements 3.4**

### Property 10: Attribute assignment completeness
*For any* product with assigned attributes, retrieving the product should return all attribute assignments with their values.
**Validates: Requirements 3.5**

### Property 11: Variation generation completeness
*For any* product with N configurable attributes where attribute i has V_i values, generating variations should create exactly V_1 × V_2 × ... × V_N variations with all unique combinations.
**Validates: Requirements 4.2**

### Property 12: Variation data independence
*For any* product variation, updating its SKU, price, or inventory should not affect other variations or the parent product.
**Validates: Requirements 4.3**

### Property 13: Soft delete preservation
*For any* variation that is disabled, the variation data should remain in the database and be retrievable with deleted_at timestamp, but excluded from active queries.
**Validates: Requirements 4.5**

### Property 14: Inventory adjustment audit trail
*For any* inventory quantity change, the system should record timestamp, user ID, and reason, creating a complete audit trail.
**Validates: Requirements 5.2**

### Property 15: Available inventory calculation
*For any* product or variation, the available inventory should equal current quantity minus reserved quantity.
**Validates: Requirements 5.4**

### Property 16: Automatic inventory decrement
*For any* product sale, the system should automatically decrement the available inventory by the sold quantity.
**Validates: Requirements 5.5**

### Property 17: Custom field validation
*For any* custom field with validation rules, entering a value should validate against those rules and reject invalid values.
**Validates: Requirements 6.3**

### Property 18: Search scope completeness
*For any* search term, the search should match against product name, SKU, description, category name, and custom field values.
**Validates: Requirements 7.1, 6.4**

### Property 19: Category filter accuracy
*For any* category filter selection, the results should include only products associated with the selected categories.
**Validates: Requirements 7.2**

### Property 20: Sort order correctness
*For any* sort field (name, SKU, price, created_at), the results should be ordered correctly in ascending or descending order.
**Validates: Requirements 7.5**

### Property 21: Upsell value constraint
*For any* upsell product relationship, the upsell product's price should be greater than or equal to the source product's price.
**Validates: Requirements 8.3**

### Property 22: Product relationship bidirectionality
*For any* product relationship (cross-sell, upsell, bundle), the relationship should be retrievable from the source product.
**Validates: Requirements 8.4**

### Property 23: Inactive product sales prevention
*For any* product with status inactive or discontinued, attempting to add it to a new quote or order should be rejected.
**Validates: Requirements 9.2, 9.3**

### Property 24: Status change audit
*For any* product status change, the system should log the change with timestamp, user, old status, and new status.
**Validates: Requirements 9.5**

### Property 25: Export completeness
*For any* product export, all product fields including custom fields should be present in the exported file.
**Validates: Requirements 10.1, 6.5**

### Property 26: Import validation error reporting
*For any* import with invalid data, the system should report specific errors with row numbers and field names for each validation failure.
**Validates: Requirements 10.3**

### Property 27: Filtered export accuracy
*For any* export with active filters, the exported data should include only products matching all filter criteria.
**Validates: Requirements 10.5**

## Error Handling

### Validation Errors

**Product Creation/Update:**
- Missing required fields (name, price)
- Invalid SKU format or duplicate SKU within team
- Invalid price (negative or non-numeric)
- Invalid currency code
- Invalid status value

**Category Management:**
- Circular parent-child relationships
- Missing required fields (name)
- Invalid parent category reference

**Attribute Management:**
- Invalid data type
- Missing required fields (name, data_type)
- Duplicate attribute slug within team

**Variation Management:**
- Duplicate SKU across variations
- Invalid attribute combinations
- Missing required variation data

**Inventory Management:**
- Negative inventory quantity
- Insufficient inventory for sale
- Invalid adjustment reason

### Business Logic Errors

**Product Operations:**
- Attempting to delete product with active orders
- Attempting to deactivate product with pending quotes
- Attempting to add inactive product to quote

**Category Operations:**
- Deleting category with assigned products (should reassign or prevent)
- Creating circular category hierarchy

**Variation Operations:**
- Generating variations without configurable attributes
- Creating variation for product without variation support

**Inventory Operations:**
- Overselling (selling more than available inventory)
- Adjusting inventory for non-tracked products

### System Errors

**Database Errors:**
- Connection failures
- Constraint violations
- Deadlocks during concurrent updates

**Media Errors:**
- Image upload failures
- Invalid image formats
- Storage quota exceeded

**Import/Export Errors:**
- File format errors
- Encoding issues
- Memory limits for large exports

### Error Response Format

All errors should follow consistent format:
```json
{
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "Product validation failed",
    "details": [
      {
        "field": "sku",
        "message": "SKU already exists for this team"
      }
    ]
  }
}
```

## Testing Strategy

### Unit Testing

**Model Tests:**
- Product model relationships (categories, variations, attributes)
- Category hierarchy methods (ancestors, descendants)
- Attribute validation logic
- Variation option parsing
- Inventory calculation methods
- Slug generation uniqueness

**Service Tests:**
- ProductService CRUD operations
- InventoryService adjustment logic
- VariationService generation algorithm
- Category tree operations

**Policy Tests:**
- Product authorization (view, create, edit, delete)
- Category authorization
- Attribute authorization
- Team-based access control

### Property-Based Testing

The system will use **Pest with Pest Plugin for Property Testing** for PHP property-based tests. Each property-based test should run a minimum of 100 iterations to ensure comprehensive coverage.

**Property Test Requirements:**
- Each correctness property MUST be implemented as a single property-based test
- Each test MUST be tagged with: `// Feature: products-inventory, Property N: [property description]`
- Tests MUST use generators to create random valid inputs
- Tests MUST verify the property holds across all generated inputs

**Key Property Tests:**

1. **Variation Generation Completeness (Property 11)**
   - Generate random sets of attributes with random value counts
   - Verify cartesian product is complete
   - Verify no duplicate combinations

2. **Inventory Calculation (Property 15)**
   - Generate random inventory states
   - Verify available = current - reserved always holds

3. **Category Hierarchy (Property 7)**
   - Generate random category trees
   - Verify filtering includes all descendants

4. **Search Completeness (Property 18)**
   - Generate random products with varied data
   - Verify search finds products in all specified fields

5. **Sort Order (Property 20)**
   - Generate random product sets
   - Verify sort order is correct for all sort fields

6. **Upsell Constraint (Property 21)**
   - Generate random product pairs
   - Verify upsell price >= source price

7. **Export Completeness (Property 25)**
   - Generate random products with all field types
   - Verify export contains all fields

**Test Generators:**
```php
// Example generators for property tests
function productGenerator(): Generator {
    return Gen::map(
        fn($name, $sku, $price) => [
            'name' => $name,
            'sku' => $sku,
            'price' => $price,
            'currency_code' => 'USD',
            'is_active' => true,
        ],
        Gen::string(),
        Gen::alphaNumString(),
        Gen::positiveFloat()
    );
}

function attributeWithValuesGenerator(): Generator {
    return Gen::map(
        fn($name, $values) => [
            'name' => $name,
            'data_type' => 'select',
            'values' => $values,
        ],
        Gen::string(),
        Gen::listOf(Gen::string(), ['min' => 2, 'max' => 5])
    );
}
```

### Integration Testing

**Filament Resource Tests:**
- Product resource CRUD operations
- Category resource operations
- Attribute resource operations
- Relation manager functionality (variations, attributes)

**API Tests:**
- Product search and filtering
- Bulk operations
- Import/export functionality

**Database Tests:**
- Transaction handling
- Concurrent updates
- Soft delete behavior
- Team scoping

### End-to-End Testing

**User Workflows:**
- Create product with categories and attributes
- Generate variations from attributes
- Manage inventory across variations
- Search and filter products
- Export and import products
- Create product bundles

**Performance Tests:**
- Large product catalog (10,000+ products)
- Complex category hierarchies (10+ levels)
- Variation generation with many attributes
- Bulk import of products
- Search performance with filters

### Test Data Management

**Factories:**
- ProductFactory with realistic data
- ProductCategoryFactory with hierarchy support
- ProductAttributeFactory with value generation
- ProductVariationFactory with option combinations

**Seeders:**
- Sample product catalog
- Category hierarchy
- Common attributes (size, color, material)
- Test variations

### Continuous Integration

**Test Pipeline:**
1. Run unit tests (fast feedback)
2. Run property-based tests (100 iterations each)
3. Run integration tests
4. Run E2E tests (critical paths only)
5. Generate coverage report (minimum 80%)

**Quality Gates:**
- All tests must pass
- Code coverage >= 80%
- No critical security issues
- No performance regressions

## Performance Considerations

### Database Optimization

**Indexes:**
- products: (team_id, slug), (team_id, sku), (team_id, is_active)
- product_categories: (team_id, slug), (parent_id)
- product_attributes: (team_id, slug)
- product_variations: (product_id), (sku)
- category_product: (product_id), (product_category_id)

**Query Optimization:**
- Eager load relationships to avoid N+1 queries
- Use select() to limit columns in list views
- Implement pagination for large result sets
- Cache category trees
- Use database transactions for multi-step operations

### Caching Strategy

**Cache Keys:**
- Category tree: `team:{team_id}:category_tree`
- Product attributes: `team:{team_id}:product:{product_id}:attributes`
- Available inventory: `product:{product_id}:available_inventory`

**Cache Invalidation:**
- Invalidate on product/category/attribute updates
- Use cache tags for bulk invalidation
- TTL: 1 hour for trees, 5 minutes for inventory

### Scalability

**Horizontal Scaling:**
- Stateless application servers
- Shared cache (Redis)
- Read replicas for reporting

**Vertical Scaling:**
- Database connection pooling
- Queue workers for imports/exports
- Background jobs for variation generation

## Security Considerations

### Authorization

- All operations must check team membership
- Policies enforce CRUD permissions
- Soft deletes prevent data loss
- Activity logging for audit trail

### Data Validation

- Sanitize all user inputs
- Validate file uploads (type, size)
- Prevent SQL injection via Eloquent
- Validate import data before processing

### API Security

- Rate limiting on search/filter endpoints
- Authentication required for all operations
- CSRF protection on forms
- XSS prevention in output

## Migration Strategy

### Database Migrations

1. Create base tables (products, categories, attributes)
2. Create relationship tables (pivots)
3. Create variation tables
4. Add indexes
5. Add foreign key constraints

### Data Migration

**From Existing System:**
1. Export existing product data
2. Map fields to new schema
3. Import products with validation
4. Import categories and associations
5. Import attributes and values
6. Generate variations if needed
7. Verify data integrity

### Rollback Plan

- All migrations reversible
- Backup before migration
- Staged rollout (test → staging → production)
- Feature flags for gradual enablement

## Future Enhancements

### Phase 2 Features

- Product bundles with dynamic pricing
- Advanced inventory management (locations, warehouses)
- Product reviews and ratings
- Product recommendations (AI-powered)
- Multi-currency pricing with exchange rates
- Bulk pricing tiers
- Product comparison tool

### Phase 3 Features

- Product configurator (visual)
- 3D product views
- Augmented reality preview
- Subscription products
- Digital products and downloads
- Product lifecycle analytics
- Automated reordering

## Dependencies

### External Packages

- **spatie/laravel-medialibrary**: Product image management
- **filament/filament**: Admin interface
- **maatwebsite/laravel-excel**: Import/export functionality
- **spatie/laravel-activitylog**: Activity tracking

### Internal Dependencies

- Team/Tenancy system
- Custom fields system
- Activity logging system
- Permission system
- Quote/Order system (for sales integration)

## Deployment Considerations

### Environment Configuration

```env
# Product Configuration
PRODUCT_DEFAULT_CURRENCY=USD
PRODUCT_IMAGE_DISK=public
PRODUCT_MAX_IMAGES=10
PRODUCT_VARIATION_LIMIT=100

# Inventory Configuration
INVENTORY_LOW_STOCK_THRESHOLD=10
INVENTORY_ENABLE_NOTIFICATIONS=true

# Import/Export Configuration
IMPORT_MAX_ROWS=10000
EXPORT_CHUNK_SIZE=1000
```

### Monitoring

**Metrics to Track:**
- Product creation rate
- Variation generation time
- Search query performance
- Import/export success rate
- Inventory adjustment frequency
- Low stock alerts

**Alerts:**
- Failed imports
- Inventory discrepancies
- Slow queries (> 1s)
- High error rates

### Backup Strategy

- Daily database backups
- Media file backups
- Backup retention: 30 days
- Test restore procedures monthly
