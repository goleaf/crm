# Filament Minimal Tabs - Maintenance Guide

## 🔧 Maintenance Checklist

This guide helps maintain and extend the MinimalTabs component.

---

## 📅 Regular Maintenance

### Monthly Tasks
- [ ] Review and update documentation if needed
- [ ] Check for Filament updates and compatibility
- [ ] Review usage across forms for consistency
- [ ] Update examples if patterns change

### Quarterly Tasks
- [ ] Review test coverage
- [ ] Update translations for new languages
- [ ] Optimize CSS if needed
- [ ] Review accessibility compliance

### Yearly Tasks
- [ ] Major version review
- [ ] Performance audit
- [ ] Documentation overhaul
- [ ] Breaking changes assessment

---

## 🐛 Bug Fixes

### When Fixing Bugs

1. **Identify the Issue**
   - Check which file is affected
   - Review error logs
   - Reproduce the issue

2. **Update Files**
   - Fix in `app/Filament/Components/MinimalTabs.php` (logic)
   - Fix in `resources/views/filament/components/minimal-tabs.blade.php` (template)
   - Fix in `resources/css/filament/admin/theme.css` (styling)

3. **Add Tests**
   - Add regression test in `tests/Feature/Filament/MinimalTabsTest.php`
   - Ensure test fails before fix
   - Verify test passes after fix

4. **Update Documentation**
   - Update troubleshooting in `docs/filament-minimal-tabs.md`
   - Add to known issues if needed
   - Update changelog

5. **Rebuild Assets**
   ```bash
   npm run build
   ```

---

## ✨ Adding Features

### When Adding New Features

1. **Plan the Feature**
   - Document requirements
   - Design API
   - Consider backward compatibility

2. **Implement**
   - Update `app/Filament/Components/MinimalTabs.php`
   - Update `resources/views/filament/components/minimal-tabs.blade.php` if needed
   - Update `resources/css/filament/admin/theme.css` if needed

3. **Test**
   - Add tests in `tests/Feature/Filament/MinimalTabsTest.php`
   - Test manually in forms
   - Test accessibility

4. **Document**
   - Update `docs/filament-minimal-tabs.md`
   - Update `docs/minimal-tabs-quick-reference.md`
   - Update `.kiro/steering/filament-minimal-tabs.md`
   - Add examples

5. **Rebuild**
   ```bash
   npm run build
   ```

---

## 📝 Updating Documentation

### Documentation Files to Update

#### For API Changes
- `docs/filament-minimal-tabs.md` - Complete guide
- `docs/minimal-tabs-quick-reference.md` - Quick reference
- `README_MINIMAL_TABS.md` - Quick start

#### For Team Guidelines
- `.kiro/steering/filament-minimal-tabs.md` - Team conventions

#### For Examples
- Update form files with new patterns
- Add to documentation examples

---

## 🧪 Testing Guidelines

### Running Tests
```bash
# Run all MinimalTabs tests
vendor/bin/pest --filter=MinimalTabsTest

# Run specific test
vendor/bin/pest --filter="can create minimal tabs component"

# Run with coverage
vendor/bin/pest --filter=MinimalTabsTest --coverage
```

### Adding Tests

1. **Test Structure**
   ```php
   it('can do something', function () {
       // Arrange
       $component = MinimalTabs::make('Test');
       
       // Act
       $result = $component->someMethod();
       
       // Assert
       expect($result)->toBeSomething();
   });
   ```

2. **Test Categories**
   - Component creation
   - Styling variants
   - Icons and badges
   - State persistence
   - Conditional visibility
   - Accessibility

---

## 🎨 Styling Updates

### Updating CSS

1. **Edit Source**
   - Update `resources/css/filament/admin/theme.css`
   - Follow Tailwind conventions
   - Maintain dark mode support

2. **Test Changes**
   - Test in light mode
   - Test in dark mode
   - Test responsive breakpoints
   - Test in different browsers

3. **Rebuild**
   ```bash
   npm run build
   ```

4. **Verify**
   - Check compiled CSS in `public/build/assets/`
   - Test in actual forms
   - Clear browser cache

---

## 🌐 Translation Updates

### Adding New Languages

1. **Copy English Keys**
   ```bash
   cp lang/en/app.php lang/{locale}/app.php
   ```

2. **Translate Keys**
   - Translate all MinimalTabs-related keys
   - Keep key names unchanged
   - Maintain placeholders

3. **Test**
   - Set locale: `app()->setLocale('{locale}')`
   - Verify translations display correctly
   - Check RTL languages if applicable

### Translation Keys
```php
'labels' => [
    'profile' => 'Profile',
    'nurturing' => 'Nurturing',
    'qualification' => 'Qualification',
    'data_quality' => 'Data Quality',
    'custom_fields' => 'Custom Fields',
    'details' => 'Details',
    'assignments' => 'Assignments',
    'sla_resolution' => 'SLA & Resolution',
    'integrations' => 'Integrations',
],
```

---

## 🔄 Filament Updates

### When Filament Updates

1. **Check Compatibility**
   - Review Filament changelog
   - Check for breaking changes
   - Test component functionality

2. **Update If Needed**
   - Update component code
   - Update tests
   - Update documentation

3. **Test Thoroughly**
   - Run all tests
   - Test in all forms
   - Test all features

4. **Document Changes**
   - Update version compatibility
   - Note any breaking changes
   - Update migration guide

---

## 📊 Performance Monitoring

### Metrics to Track

1. **Build Performance**
   - Build time (currently ~10s)
   - Asset sizes
   - Compilation warnings

2. **Runtime Performance**
   - Tab switching speed
   - Initial render time
   - Memory usage

3. **User Experience**
   - Tab usage patterns
   - Error rates
   - Accessibility issues

### Optimization Tips

1. **CSS**
   - Remove unused styles
   - Optimize selectors
   - Minimize specificity

2. **JavaScript**
   - Optimize Alpine.js logic
   - Reduce DOM manipulation
   - Lazy load if needed

3. **Assets**
   - Compress CSS
   - Minify JavaScript
   - Optimize images

---

## 🔍 Code Review Checklist

### Before Merging Changes

- [ ] Code follows Filament v4.3+ conventions
- [ ] Tests added/updated and passing
- [ ] Documentation updated
- [ ] Assets rebuilt
- [ ] No console errors
- [ ] Accessibility maintained
- [ ] Dark mode works
- [ ] Responsive design intact
- [ ] Backward compatible (or documented)
- [ ] Translation keys added if needed

---

## 📦 Version Management

### Versioning Strategy

- **Major** (1.0.0 → 2.0.0): Breaking changes
- **Minor** (1.0.0 → 1.1.0): New features
- **Patch** (1.0.0 → 1.0.1): Bug fixes

### Release Process

1. **Prepare Release**
   - Update version number
   - Update changelog
   - Run all tests
   - Build assets

2. **Document Release**
   - List changes
   - Note breaking changes
   - Provide migration guide

3. **Deploy**
   - Merge to main
   - Tag release
   - Update documentation

---

## 🆘 Common Issues

### Issue: Tabs Not Rendering
**Solution**: Check component import and syntax

### Issue: Icons Missing
**Solution**: Verify Heroicon name format

### Issue: State Not Persisting
**Solution**: Check persistence method is called

### Issue: Styling Broken
**Solution**: Rebuild assets with `npm run build`

### Issue: Tests Failing
**Solution**: Check for breaking changes in dependencies

---

## 📞 Support Resources

### Internal Resources
- Component: `app/Filament/Components/MinimalTabs.php`
- Template: `resources/views/filament/components/minimal-tabs.blade.php`
- Styles: `resources/css/filament/admin/theme.css`
- Tests: `tests/Feature/Filament/MinimalTabsTest.php`

### Documentation
- Complete guide: `docs/filament-minimal-tabs.md`
- Quick reference: `docs/minimal-tabs-quick-reference.md`
- Team guidelines: `.kiro/steering/filament-minimal-tabs.md`
- Quick start: `README_MINIMAL_TABS.md`

### Examples
- CRM Settings: `app/Filament/Pages/CrmSettings.php`
- Knowledge Article: `app/Filament/Resources/KnowledgeArticleResource/Forms/KnowledgeArticleForm.php`
- Lead Form: `app/Filament/Resources/LeadResource/Forms/LeadForm.php`
- Support Case: `app/Filament/Resources/SupportCaseResource/Forms/SupportCaseForm.php`

---

## 🎯 Quick Commands

```bash
# Run tests
vendor/bin/pest --filter=MinimalTabsTest

# Build assets
npm run build

# Watch for changes
npm run dev

# Clear cache
php artisan optimize:clear

# Run linter
composer lint

# Run full test suite
composer test
```

---

**Last Updated**: December 9, 2025  
**Component Version**: 1.0.0  
**Maintainer**: Development Team
