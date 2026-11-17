# Changelog

## [1.0.1] - 2026-11-17

### Fixed
- Configured a deterministic test-only Laravel application encryption key in the Orchestra Testbench environment so the encrypted social-account credentials cast can be exercised during feature tests.
- Production applications continue using their own `APP_KEY`; no production encryption behavior or database schema changes were made.

## [1.0.0] - 2026-11-17

### Added
- Polymorphic social account ownership.
- Encrypted account credential storage.
- Draft creation and scheduled post lifecycle.
- Cancellation of scheduled posts.
- Platform publisher contract.
- Publication attempt and provider response tracking.
- Success and failure state handling.
- PHPUnit feature tests and GitHub Actions workflow.
