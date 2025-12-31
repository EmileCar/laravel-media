# GitHub Actions Setup Guide

This package uses GitHub Actions to automate testing and releases.

## Workflows

### 1. Tests (`tests.yml`)
- Runs on every push to `main`/`develop` and on pull requests to `main`
- Tests against PHP 8.2 & 8.3
- Tests against Laravel 11.* & 12.*
- Must pass before merging to main

### 2. Release (`release.yml`)
- Automatically runs when code is merged to `main`
- Reads version from `composer.json`
- Creates a git tag (e.g., `v1.0.0`)
- Creates a GitHub release
- Notifies Packagist (optional)

### 3. Branch Protection (`branch-protection.yml`)
- Ensures tests pass before allowing merge to main

## Setup Instructions

### 1. Enable GitHub Actions
GitHub Actions should be enabled by default. Verify in:
- Repository → Settings → Actions → General
- Ensure "Allow all actions and reusable workflows" is selected

### 2. Configure Branch Protection (Recommended)
Protect the `main` branch to require tests before merging:

1. Go to: Repository → Settings → Branches
2. Add rule for `main` branch
3. Enable:
   - ✅ Require a pull request before merging
   - ✅ Require status checks to pass before merging
   - ✅ Require branches to be up to date before merging
4. Add required status checks:
   - `All tests passed`
   - Or individual test matrix jobs
5. Save changes

### 3. Packagist Integration (Optional)
Packagist auto-updates from GitHub webhooks, but you can add manual notification:

1. Get your Packagist API token:
   - Log in to [Packagist.org](https://packagist.org)
   - Go to Profile → Settings → API Token
   - Generate/copy your API token

2. Add GitHub secrets:
   - Repository → Settings → Secrets and variables → Actions
   - Add `PACKAGIST_USERNAME` (your Packagist username)
   - Add `PACKAGIST_TOKEN` (your API token)

**Note:** This step is optional. Packagist usually auto-updates within 10 minutes via GitHub webhooks.

### 4. Submit to Packagist
If not already done:

1. Go to [Packagist.org](https://packagist.org/packages/submit)
2. Submit: `https://github.com/YOUR_USERNAME/laravel-media`
3. Enable GitHub service hook (usually automatic)

## How to Release a New Version

1. Update the `version` field in `composer.json`:
   ```json
   {
     "version": "1.2.0"
   }
   ```

2. Commit and push to a feature branch:
   ```bash
   git add composer.json
   git commit -m "Bump version to 1.2.0"
   git push origin feature/version-bump
   ```

3. Create a pull request to `main`
   - Tests will run automatically
   - Merge only if tests pass

4. Merge to `main`:
   - The release workflow will automatically:
     - Create tag `v1.2.0`
     - Create GitHub release
     - Notify Packagist
   - Packagist will update within minutes

## Version Naming Convention

Follow [Semantic Versioning](https://semver.org/):
- `1.0.0` - Major version (breaking changes)
- `1.1.0` - Minor version (new features, backward compatible)
- `1.1.1` - Patch version (bug fixes)

## Troubleshooting

### Tests failing?
- Check the Actions tab for detailed logs
- Run tests locally: `composer test` or `vendor/bin/phpunit`
- Ensure all dependencies are compatible

### Tag already exists?
- Update the version in `composer.json` to a new version
- The workflow checks if a tag exists before creating it

### Packagist not updating?
- Check Packagist package page for update status
- Manually trigger update on Packagist website
- Verify GitHub webhook is configured in Packagist settings

### GitHub Actions not running?
- Check if Actions are enabled in repository settings
- Verify workflow files are in `.github/workflows/` directory
- Check for syntax errors in YAML files

## Local Testing

Before pushing, test locally:

```bash
# Run all tests
vendor/bin/phpunit

# Run specific test
vendor/bin/phpunit tests/YourTestFile.php

# Run with coverage
vendor/bin/phpunit --coverage-html coverage
```

## CI Badge

Add to your README.md:

```markdown
[![Tests](https://github.com/YOUR_USERNAME/laravel-media/actions/workflows/tests.yml/badge.svg)](https://github.com/YOUR_USERNAME/laravel-media/actions/workflows/tests.yml)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/carone/laravel-media.svg?style=flat-square)](https://packagist.org/packages/carone/laravel-media)
```
