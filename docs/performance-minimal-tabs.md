# MinimalTabs Performance Guide

## Performance Targets

The MinimalTabs component maintains strict performance standards:

### Benchmarks
- **CSS Class Operations**: < 100ms with 1000+ classes
- **Memory Usage**: < 1MB for 10,000 operations
- **Scaling**: Linear O(n) time complexity
- **Operation Consistency**: Max 3x variance between operations

### Real-World Performance

#### Typical Usage Scenarios
1. **Small Forms** (2-5 tabs, 10-20 classes): < 1ms
2. **Complex Forms** (10+ tabs, 100+ classes): < 10ms
3. **Dynamic Forms** (frequent class changes): < 5ms per operation

#### Performance Optimizations Applied

##### 1. Quick Existence Check
```php
// Fast string check before expensive array operations
if ($existingClasses !== '' && str_contains($existingClasses, $class)) {
    $pattern = '/\b' . preg_quote($class, '/') . '\b/';
    if (preg_match($pattern, $existingClasses)) {
        return; // Class already exists
    }
}
```

##### 2. Filament v4.3+ Schema Compatibility
- Updated namespace: `Filament\Schemas\Components\Tabs`
- Optimized for unified schema system
- Reduced component overhead

### Monitoring

#### Performance Tests
Run performance benchmarks:
```bash
vendor/bin/pest tests/Unit/Filament/Components/MinimalTabsPerformanceTest.php
```

#### Memory Profiling
```php
// Monitor memory usage in development
$initialMemory = memory_get_usage();
$tabs = MinimalTabs::make('Test')->minimal()->compact();
$memoryUsed = memory_get_usage() - $initialMemory;
```

### Best Practices

#### DO:
- ✅ Use method chaining for multiple operations
- ✅ Cache component instances when reusing
- ✅ Leverage existing performance tests
- ✅ Monitor memory usage in complex forms

#### DON'T:
- ❌ Create new instances unnecessarily
- ❌ Apply/remove same classes repeatedly
- ❌ Use in high-frequency loops without caching

### Integration with Filament v4.3+

The component is optimized for Filament's unified schema system:

```php
use Filament\Schemas\Schema;
use App\Filament\Components\MinimalTabs;

Schema::make()
    ->components([
        MinimalTabs::make('Settings')
            ->tabs([...])
            ->minimal()
            ->compact(), // Optimized method chaining
    ]);
```

### Troubleshooting

#### Performance Issues
1. **Slow class operations**: Check for excessive class counts (>1000)
2. **Memory leaks**: Verify component instances are properly garbage collected
3. **Rendering delays**: Ensure CSS is properly optimized

#### Debugging
```php
// Enable performance logging in development
if (app()->environment('local')) {
    $startTime = microtime(true);
    $tabs = MinimalTabs::make('Debug')->minimal();
    $executionTime = microtime(true) - $startTime;
    logger("MinimalTabs execution time: {$executionTime}s");
}
```

## Version History

### v2.0.0 (Current)
- ✅ Filament v4.3+ schema compatibility
- ✅ Optimized CSS class existence checking
- ✅ Enhanced performance monitoring
- ✅ 57 comprehensive tests

### v1.0.0
- Initial implementation with performance benchmarks
- CSS class management bug fixes
- Comprehensive test coverage