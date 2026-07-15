# Changelog

All notable changes to SymPress Coding Standards are documented in this file.

The format follows Keep a Changelog style sections and semantic versioning.

## Unreleased

### Added

- Added `SymPress-Enterprise-LTS`, `SymPress-Enterprise-Modern`, and `SymPress-Enterprise-Next` compatibility profiles.
- Added enterprise adoption, compatibility, rule strategy, and release trust documentation.

### Changed

- Raised the package runtime requirement to PHP 8.5+.
- Allowed stable PHPCompatibility 9.x in addition to PHPCompatibility 10 alpha releases.
- Made base layers version-neutral and mapped `SymPress-Plugin`, `SymPress-Core`, and `SymPress-Extra` to `SymPress-Enterprise-Next`.
- Updated `SymPress-Templates` to include the WordPress security layer before applying template-specific rules.

### Fixed

- Prevented recommended template path configurations from dropping WordPress escaping, i18n, nonce, and validated-input checks.
