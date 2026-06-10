# Compatibility

SymPress Coding Standards separates the PHP version required to run the standard from the PHP and WordPress versions a project wants to enforce.

## Runtime Requirement

The package can be installed on PHP 8.2 or newer. Projects that use syntax introduced after the PHP version running PHP_CodeSniffer should run PHPCS on the same PHP minor version as the project target, because tokenization of newer language syntax depends on the executing PHP runtime.

## Enterprise Profiles

| Profile | Intended use | PHPCompatibility `testVersion` | WordPress minimum |
| ------- | ------------ | ------------------------------ | ----------------- |
| `SymPress-Enterprise-LTS` | Conservative enterprise stacks and large legacy migrations | `8.2-` | `6.5` |
| `SymPress-Enterprise-Modern` | Default profile for current enterprise WordPress work | `8.4-` | `7.0` |
| `SymPress-Enterprise-Next` | Strict profile for new codebases on the newest supported stack | `8.5-` | `7.0` |

The base layers `SymPress-Pure`, `SymPress-WordPress`, `SymPress-Boundary`, and `SymPress-Templates` are version-neutral. The enterprise profiles set the compatibility target.

The historical `SymPress-Plugin`, `SymPress-Core`, and `SymPress-Extra` standards remain available for compatibility and map to `SymPress-Enterprise-Next`. New enterprise projects should prefer one of the enterprise profiles so the intended support level is explicit in the project ruleset.

## PHPCompatibility

The Composer constraint allows stable PHPCompatibility 9.x and PHPCompatibility 10 alpha releases:

```json
"phpcompatibility/php-compatibility": "^9.3 || ^10.0@alpha"
```

Use PHPCompatibility 10 when checking the newest PHP language versions. Use the stable 9.x line when an organization cannot approve alpha dependencies and the target PHP version is covered by that line.

## WordPress VIP Positioning

`SymPress-WordPress` includes selected `WordPressVIPMinimum` checks for security, performance, hook behavior, and platform safety. This package is VIP-aware, not a replacement for WordPress VIP project review or for running the official VIP standard required by a hosting contract.

For VIP-hosted projects, run the SymPress profile and the official VIP standard in CI until the project team has documented that the overlap is understood.

## PHPCS 4 Readiness

The default dependency range stays on PHP_CodeSniffer 3.x until WordPress Coding Standards, VIPWPCS, and the custom SymPress sniffs are verified together on PHPCS 4. The release process tracks PHPCS 4 as an experimental matrix item. PHPCS 4 support should be shipped as a minor release only when all bundled profiles pass without compatibility shims; any rule behavior change that alters default findings must be called out in the changelog.
