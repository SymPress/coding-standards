# Changelog

All notable changes to SymPress Coding Standards are documented in this file.

The format follows Keep a Changelog style sections and semantic versioning.

## 1.1.2 - 2026-11-23

### Fixed

- `SymPress.Strings.VariableInDoubleQuotes`: the fixer now braces the whole simple interpolation (`"$user->name"` becomes `"{$user->name}"`, `"$items[key]"` becomes `"{$items['key']}"`) and ignores escaped `\$`. It used to brace only the variable name, which changed the string's value. Offsets without an identical braced form, such as `$items[01]`, are reported but no longer fixed.
- `SymPress.Usage.IsNull`: the fixer keeps the parentheses around any argument that contains an operator (`is_null($a??$b)` becomes `($a??$b) === null`). It now fixes only where `=== null` cannot bind to another operand; calls next to `.`, casts or comparisons are reported but not fixed.
- `SymPress.Formatting.UnnecessaryNamespaceUsage`: names from the current namespace are no longer shortened when an import already uses the short name (`new \Probe\Thing()` next to `use Zeta\Thing;`).
- `SymPress.Functions.StaticClosure`: closures that `include`, `require` or `eval` code are still reported but no longer made `static` automatically, because the included code may use `$this`.

### Changed

- Committed `composer.lock`, updated the Composer author email and formatted `composer.json`.
- CI: pinned `sympress/workflows` to v1.3.2 and let Dependabot update GitHub Actions pins.

### Documentation

- README: `SymPress-WordPress`, `SymPress-Boundary` and `SymPress-Templates` need a `testVersion` when used directly. Without it, PHPCompatibility 9.3.5 aborts every file on PHP 8.4+.
- Adoption guide: convert `else if` to `elseif` before the first run, because `SlevomatCodingStandard.ControlStructures.EarlyExit` can abort the whole file on `else if`.

### Compatibility and finding churn

- No ruleset, severity or dependency changes. PHP 8.5+, PHPCS 3.13, WPCS 3, VIPWPCS 3 and PHPCompatibility 9.3/10 are unchanged.
- Small churn. `VariableInDoubleQuotes` now also reports variables after a literal `{` (for example `"json { key: $x"`), which the old heuristic skipped. It no longer reports escaped `\$name`. `UnnecessaryNamespaceUsage` no longer warns where shortening would change the class. Some `IsNull`, `VariableInDoubleQuotes` and `StaticClosure` findings no longer carry the `[x]` fixable marker.

## 1.1.1 and earlier

Changes up to 1.1.1, recorded before the changelog used per-version sections.

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
