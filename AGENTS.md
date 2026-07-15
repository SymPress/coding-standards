# SymPress Coding Standards agent contract

## Purpose and boundaries

This package owns reusable PHPCS profiles and the custom `SymPress.*` sniffs. Reuse an established vendor sniff before adding a custom rule. Keep WordPress-specific allowances out of the pure PHP layer.

## Read first

- `docs/Rules.md`: intent, severity and false-positive posture for every custom rule.
- `docs/Sniffs.md`: compact custom-sniff inventory.
- `docs/Compatibility.md` and `docs/Adoption.md`: supported profiles and rollout policy.
- `SymPress-*/ruleset.xml`: public profile composition.
- `tests/fixtures` and `tests/unit/FixturesTest.php`: executable sniff expectations.

## Verification

- Fast for one sniff: run the matching PHPUnit test or `composer tests -- --filter <name>`.
- Full: `composer qa`.
- Use `composer cs:fix` only for intended mechanical fixes, then inspect the diff.

## Invariants

- New custom sniffs must fill a documented gap rather than duplicate a vendor rule.
- Pure, WordPress, boundary and template profiles keep their layer-specific responsibilities.
- Every custom sniff needs fixture expectations and entries in both documentation catalogs.
- Fixers must be idempotent and preserve semantics.
- Runtime and compatibility claims must match `composer.json` and the public rulesets.

## Cross-repository impact

`sympress/qa` and most SymPress PHP repositories consume these profiles. A changed default severity or profile inclusion can break every consumer and requires release notes plus representative downstream validation.

## Definition of done

Fixtures cover positive and negative behavior, catalog validation passes, `composer qa` is green, and compatibility/changelog text matches the manifest.
