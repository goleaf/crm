# Localazy GitHub Actions Integration

## Overview

This document describes the integration of Localazy with GitHub Actions for automated translation management in the Relaticle CRM project. Localazy provides a cloud-based translation management platform that integrates seamlessly with our existing Laravel Translation Checker setup.

## Architecture

### Translation Flow

```
┌─────────────────────────────────────────────────────────────────┐
│                     Translation Workflow                         │
└─────────────────────────────────────────────────────────────────┘

1. Developer adds/updates English translations
   ↓
2. Commit to main/develop branch
   ↓
3. GitHub Action: Upload to Localazy
   ↓
4. Translators work in Localazy UI
   ↓
5. Translations completed
   ↓
6. GitHub Action: Download from Localazy (scheduled/webhook)
   ↓
7. Import to Laravel Translation Checker database
   ↓
8. Commit updated translation files
   ↓
9. Deploy to production
```

### Components

1. **Localazy Platform**: Cloud-based translation management
2. **GitHub Actions**: Automated upload/download workflows
3. **Laravel Translation Checker**: Database-backed translation storage
4. **PHP Translation Files**: Version-controlled translation files

## Setup

### 1. Localazy Account Setup

1. Create a Localazy account at https://localazy.com
2. Create a new project for Relaticle CRM
3. Configure project settings:
   - **Source Language**: English (en)
   - **Target Languages**: Ukrainian (uk), Spanish (es), German (de), French (fr), etc.
   - **File Format**: PHP Arrays

### 2. Generate API Keys

1. Navigate to Project Settings → API Keys
2. Generate a **Read Key** (for downloading translations)
3. Generate a **Write Key** (for uploading source translations)
4. Save both keys securely

### 3. Configure GitHub Secrets

Add the following secrets to your GitHub repository:

```bash
# Navigate to: Settings → Secrets and variables → Actions → New repository secret

LOCALAZY_READ_KEY=your_read_key_here
LOCALAZY_WRITE_KEY=your_write_key_here
```

### 4. Configuration File

The `localazy.json` file in the project root configures file mappings:

```json
{
  "writeKey": "${LOCALAZY_WRITE_KEY}",
  "readKey": "${LOCALAZY_READ_KEY}",
  "upload": {
    "type": "php",
    "files": [
      {
        "file": "lang/en/app.php",
        "path": "app.php"
      },
      // ... more files
    ]
  },
  "download": {
    "files": "lang/${lang}/${file}"
  }
}
```

## GitHub Actions Workflows

### Upload Workflow (`localazy-upload.yml`)

**Triggers:**
- Push to `main` or `develop` branches
- Changes to `lang/en/**/*.php` files
- Changes to module translations: `app-modules/*/src/resources/lang/en/**/*.php`
- Manual trigger via workflow_dispatch

**Steps:**
1. Checkout repository
2. Setup PHP 8.4 environment
3. Install Composer dependencies
4. Export translations from database (`php artisan translations:export --language=en`)
5. Upload to Localazy using `localazy/upload@v1` action
6. Create workflow summary

**Usage:**
```bash
# Automatic: Push changes to English translation files
git add lang/en/app.php
git commit -m "feat: add new translation keys"
git push origin main

# Manual: Trigger via GitHub UI
# Navigate to: Actions → Localazy Upload → Run workflow
```

### Download Workflow (`localazy-download.yml`)

**Triggers:**
- Scheduled: Daily at 2 AM UTC
- Webhook: When translations are updated in Localazy
- Manual trigger via workflow_dispatch

**Steps:**
1. Checkout repository
2. Setup PHP 8.4 environment
3. Install Composer dependencies
4. Download translations from Localazy using `localazy/download@v1` action
5. Import to database (`php artisan translations:import`)
6. Commit and push changes if translations were updated
7. Create workflow summary

**Usage:**
```bash
# Automatic: Runs daily at 2 AM UTC

# Manual: Trigger via GitHub UI
# Navigate to: Actions → Localazy Download → Run workflow

# Webhook: Configure in Localazy project settings
# Webhook URL: https://api.github.com/repos/{owner}/{repo}/dispatches
# Event type: localazy-updated
# Headers: Authorization: token YOUR_GITHUB_TOKEN
```

## Integration with Laravel Translation Checker

### Workflow

1. **Upload Phase:**
   - Export English translations from database to PHP files
   - Upload PHP files to Localazy
   - Translators work in Localazy UI

2. **Download Phase:**
   - Download translated PHP files from Localazy
   - Import PHP files to Laravel Translation Checker database
   - Commit updated files to version control

### Commands

```bash
# Export translations from database to PHP files
php artisan translations:export

# Export specific language
php artisan translations:export --language=uk

# Import translations from PHP files to database
php artisan translations:import

# Import specific language
php artisan translations:import --language=uk

# Sync database with filesystem
php artisan translations:sync
```

## Localazy Webhook Configuration

### Setup Webhook in Localazy

1. Navigate to Project Settings → Webhooks
2. Add new webhook:
   - **URL**: `https://api.github.com/repos/{owner}/{repo}/dispatches`
   - **Event**: Translation completed
   - **Headers**:
     ```
     Authorization: token YOUR_GITHUB_TOKEN
     Accept: application/vnd.github.v3+json
     Content-Type: application/json
     ```
   - **Payload**:
     ```json
     {
       "event_type": "localazy-updated"
     }
     ```

### Generate GitHub Token

1. Navigate to GitHub Settings → Developer settings → Personal access tokens
2. Generate new token (classic) with `repo` scope
3. Use token in webhook configuration

## Translation Workflow

### For Developers

1. **Add New Translation Keys:**
   ```php
   // lang/en/app.php
   return [
       'labels' => [
           'new_feature' => 'New Feature',
       ],
   ];
   ```

2. **Commit and Push:**
   ```bash
   git add lang/en/app.php
   git commit -m "feat: add new feature translations"
   git push origin main
   ```

3. **Automatic Upload:**
   - GitHub Action uploads to Localazy
   - Translators are notified

4. **Wait for Translations:**
   - Translators complete work in Localazy
   - Webhook triggers download workflow
   - Translations are automatically imported

### For Translators

1. **Access Localazy UI:**
   - Login to Localazy
   - Navigate to Relaticle CRM project

2. **Translate Strings:**
   - Select target language
   - Translate missing strings
   - Review and approve translations

3. **Automatic Sync:**
   - Completed translations trigger webhook
   - GitHub Action downloads and commits changes
   - Changes are deployed in next release

## Monitoring

### GitHub Actions

Monitor workflow runs:
- Navigate to: Actions → Localazy Upload/Download
- View logs, summaries, and artifacts
- Check for errors or warnings

### Localazy Dashboard

Monitor translation progress:
- Navigate to: Project Dashboard
- View completion percentages per language
- Track translator activity
- Review translation quality

### Laravel Translation Checker

Monitor via Filament UI:
- Navigate to: Settings → Translations
- View completion statistics
- Check missing translations
- Review translation history

## Best Practices

### DO:
- ✅ Always export from database before uploading to Localazy
- ✅ Import downloaded translations to database immediately
- ✅ Review translation changes in pull requests
- ✅ Use descriptive commit messages for translation updates
- ✅ Monitor workflow runs for errors
- ✅ Keep `localazy.json` updated with new translation files
- ✅ Use webhook for real-time translation updates
- ✅ Test translations in staging before production

### DON'T:
- ❌ Edit translation files manually without syncing to database
- ❌ Skip the import step after downloading from Localazy
- ❌ Commit translation files without running workflows
- ❌ Ignore workflow failures
- ❌ Mix manual and automated translation workflows
- ❌ Deploy without verifying translation completeness
- ❌ Forget to update module translations in `localazy.json`

## Troubleshooting

### Upload Workflow Fails

**Problem**: Upload action fails with authentication error

**Solution**:
1. Verify `LOCALAZY_WRITE_KEY` secret is set correctly
2. Check API key permissions in Localazy
3. Ensure `localazy.json` is valid JSON
4. Review workflow logs for specific error messages

### Download Workflow Fails

**Problem**: Download action fails or no changes detected

**Solution**:
1. Verify `LOCALAZY_READ_KEY` secret is set correctly
2. Check if translations are completed in Localazy
3. Ensure target languages are configured in Localazy project
4. Review workflow logs for specific error messages

### Import Fails

**Problem**: `php artisan translations:import` fails

**Solution**:
1. Check PHP file syntax for errors
2. Verify database connection
3. Ensure Laravel Translation Checker is properly configured
4. Check file permissions on `lang/` directory
5. Review Laravel logs: `storage/logs/laravel.log`

### Webhook Not Triggering

**Problem**: Webhook doesn't trigger download workflow

**Solution**:
1. Verify webhook URL is correct
2. Check GitHub token has `repo` scope
3. Test webhook manually in Localazy settings
4. Review webhook delivery logs in Localazy
5. Check GitHub Actions permissions

### Merge Conflicts

**Problem**: Translation file merge conflicts

**Solution**:
1. Always pull latest changes before pushing
2. Use `php artisan translations:sync` to resolve conflicts
3. Prefer database as source of truth
4. Re-export after resolving conflicts
5. Consider using separate branches for translation updates

## Performance Optimization

### Caching

GitHub Actions caches Composer dependencies:
```yaml
- name: Cache Dependencies
  uses: actions/cache@v4
  with:
    path: ~/.composer/cache/files
    key: dependencies-php-composer-${{ hashFiles('composer.lock') }}
```

### Selective Uploads

Only upload changed files by configuring `localazy.json`:
```json
{
  "upload": {
    "type": "php",
    "files": [
      {
        "file": "lang/en/app.php",
        "path": "app.php",
        "buildType": "incremental"
      }
    ]
  }
}
```

### Parallel Processing

Download multiple languages in parallel:
```yaml
strategy:
  matrix:
    language: [uk, es, de, fr]
```

## Security Considerations

### API Keys

- Store API keys in GitHub Secrets (never in code)
- Rotate keys periodically
- Use separate keys for different environments
- Limit key permissions to minimum required

### Access Control

- Restrict who can trigger manual workflows
- Review translation changes before merging
- Use branch protection rules
- Require code review for translation updates

### Audit Trail

- All translation changes are committed to Git
- GitHub Actions logs provide audit trail
- Localazy tracks translator activity
- Laravel Translation Checker logs changes

## Cost Considerations

### Localazy Pricing

- **Free Tier**: Up to 1,000 source strings
- **Starter**: $39/month for 5,000 strings
- **Business**: $99/month for 20,000 strings
- **Enterprise**: Custom pricing

### GitHub Actions

- **Free Tier**: 2,000 minutes/month for private repos
- **Pro**: 3,000 minutes/month
- **Team**: 10,000 minutes/month

### Optimization

- Use scheduled workflows instead of frequent polling
- Cache dependencies to reduce build time
- Use webhooks for real-time updates
- Limit workflow runs to necessary branches

## Migration from Manual Translation

### Step 1: Export Existing Translations

```bash
# Export all languages from database
php artisan translations:export

# Verify files are up to date
git status
```

### Step 2: Upload to Localazy

```bash
# Trigger upload workflow manually
# Navigate to: Actions → Localazy Upload → Run workflow
```

### Step 3: Verify in Localazy

1. Login to Localazy
2. Check all translation files are uploaded
3. Verify translation counts match expectations
4. Review translation quality

### Step 4: Configure Webhooks

1. Setup webhook in Localazy
2. Test webhook delivery
3. Verify download workflow triggers

### Step 5: Update Documentation

1. Update team documentation
2. Train translators on Localazy UI
3. Document new workflow for developers

## Related Documentation

- `.kiro/steering/translation-checker.md` - Laravel Translation Checker integration
- `.kiro/steering/translations.md` - Translation conventions
- `.kiro/steering/TRANSLATION_GUIDE.md` - Translation implementation guide
- `docs/laravel-translation-checker-integration.md` - Complete integration guide
- [Localazy Documentation](https://localazy.com/docs)
- [Localazy GitHub Actions](https://github.com/localazy/upload)

## Support

### Localazy Support

- Documentation: https://localazy.com/docs
- Support: support@localazy.com
- Community: https://localazy.com/community

### Internal Support

- Translation issues: Check Filament Translation Management page
- Workflow issues: Review GitHub Actions logs
- Technical issues: Check Laravel logs and database

## Changelog

### 2025-01-XX - Initial Integration

- Added Localazy GitHub Actions workflows
- Created `localazy.json` configuration
- Integrated with Laravel Translation Checker
- Documented setup and usage
- Added webhook support for real-time updates
