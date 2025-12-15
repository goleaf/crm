# Localazy Translation Management Integration

> **📚 Comprehensive Guide**: See `docs/localazy-github-actions-integration.md` for complete workflow setup, webhook configuration, and troubleshooting.

## Core Principles
- Localazy provides cloud-based translation management integrated with GitHub Actions.
- Automated upload of English source translations when `lang/en/**/*.php` files change.
- Automated download of completed translations via scheduled workflow or webhook.
- Seamless integration with Laravel Translation Checker for database-backed storage.
- All translation changes are version-controlled and auditable.

## GitHub Actions Workflows

### Upload Workflow
- **Triggers**: Push to main/develop with changes to `lang/en/**/*.php` or module translations
- **Process**: Export from database → Upload to Localazy
- **File**: `.github/workflows/localazy-upload.yml`

### Download Workflow
- **Triggers**: Daily at 2 AM UTC, webhook from Localazy, or manual
- **Process**: Download from Localazy → Import to database → Commit changes
- **File**: `.github/workflows/localazy-download.yml`

## Configuration

### Localazy Config (`localazy.json`)
- Maps PHP translation files to Localazy paths
- Defines upload/download patterns
- Uses environment variables for API keys

### GitHub Secrets
- `LOCALAZY_READ_KEY` - For downloading translations
- `LOCALAZY_WRITE_KEY` - For uploading source translations

## Workflow for Developers

### Adding New Translations
1. Add keys to `lang/en/app.php` (or other files)
2. Commit and push to main/develop
3. GitHub Action automatically uploads to Localazy
4. Translators are notified in Localazy UI

### Receiving Translations
1. Translators complete work in Localazy
2. Webhook triggers download workflow (or runs on schedule)
3. Translations are imported to database
4. Changes are committed automatically
5. Deploy in next release

## Integration with Translation Checker

### Export Before Upload
```bash
# Export from database to PHP files
php artisan translations:export --language=en
```

### Import After Download
```bash
# Import from PHP files to database
php artisan translations:import
```

### Sync Workflow
```bash
# Sync database with filesystem
php artisan translations:sync
```

## Webhook Configuration

### Localazy Webhook Setup
- **URL**: `https://api.github.com/repos/{owner}/{repo}/dispatches`
- **Event**: Translation completed
- **Payload**: `{"event_type": "localazy-updated"}`
- **Headers**: GitHub token with `repo` scope

## Monitoring

### GitHub Actions
- View workflow runs in Actions tab
- Check logs for errors
- Review summaries for changed files

### Localazy Dashboard
- Monitor translation progress
- Track completion percentages
- Review translator activity

### Filament UI
- Settings → Translations
- View completion statistics
- Check missing translations

## Best Practices

### DO:
- ✅ Always export from database before uploading
- ✅ Import downloaded translations immediately
- ✅ Review translation changes in PRs
- ✅ Use webhook for real-time updates
- ✅ Monitor workflow runs for errors
- ✅ Keep `localazy.json` updated with new files

### DON'T:
- ❌ Edit translation files manually without syncing
- ❌ Skip import step after downloading
- ❌ Ignore workflow failures
- ❌ Mix manual and automated workflows
- ❌ Deploy without verifying completeness

## Troubleshooting

### Upload Fails
- Verify `LOCALAZY_WRITE_KEY` secret
- Check `localazy.json` syntax
- Review workflow logs

### Download Fails
- Verify `LOCALAZY_READ_KEY` secret
- Check translations are completed in Localazy
- Ensure target languages are configured

### Import Fails
- Check PHP file syntax
- Verify database connection
- Review Laravel logs

### Webhook Not Triggering
- Verify webhook URL and token
- Test webhook manually in Localazy
- Check GitHub Actions permissions

## Performance

### Caching
- Composer dependencies cached in workflows
- Translation data cached in Laravel (1-hour TTL)

### Optimization
- Use scheduled workflows instead of frequent polling
- Use webhooks for real-time updates
- Limit workflow runs to necessary branches

## Security

### API Keys
- Store in GitHub Secrets (never in code)
- Rotate periodically
- Use separate keys per environment
- Limit permissions to minimum required

### Access Control
- Restrict manual workflow triggers
- Review translation changes before merging
- Use branch protection rules
- Require code review for updates

## Related Documentation
- `docs/localazy-github-actions-integration.md` - Complete integration guide
- `.kiro/steering/translation-checker.md` - Laravel Translation Checker
- `.kiro/steering/translations.md` - Translation conventions
- `.kiro/steering/TRANSLATION_GUIDE.md` - Implementation guide

## Quick Commands

```bash
# Export translations to PHP files
php artisan translations:export

# Import translations from PHP files
php artisan translations:import

# Sync database with filesystem
php artisan translations:sync

# Check translation completeness
php artisan translations:check

# Trigger upload workflow (manual)
# Navigate to: Actions → Localazy Upload → Run workflow

# Trigger download workflow (manual)
# Navigate to: Actions → Localazy Download → Run workflow
```
