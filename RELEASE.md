# Release checklist

1. Run the full test suite locally.
2. Confirm the GitHub Actions matrix passes for supported PHP and Laravel versions.
3. Commit and push the package source to `main`.
4. Create and push the annotated `v1.0.1` tag.
5. Create the GitHub Release using the title and notes below.
6. Confirm Packagist detects the new tag.

## Git commands (run locally)

```bash
git add .
git status
git commit -m "test: configure encryption key in Testbench"
git push origin main

git tag -a v1.0.1 -m "Release v1.0.1"
git push origin v1.0.1
```

## GitHub Release

Title: `v1.0.1 - Configure Testbench Encryption Key`

```markdown
## Bug Fixes
- Configured a deterministic test-only Laravel application encryption key in the Orchestra Testbench environment.
- This allows feature tests to exercise the encrypted social-account credentials cast without requiring a real application `.env` file.

## Security
- Production encryption behavior is unchanged.
- Applications must continue to configure and protect their own `APP_KEY`.

## Compatibility
- PHP 8.3+
- Laravel 12 and 13

## Upgrade
composer update itmosoft/laravel-social-publishing

No database migration changes are required.
```

## Packagist

Verify Packagist detects `v1.0.1` after the GitHub tag is pushed.
