# Release and Trust Policy

SymPress Coding Standards follows the SymPress organization defaults for contribution, security, and community behavior. Package-specific trust requirements live here because ruleset changes can alter CI outcomes for downstream projects.

## Semantic Versioning

Use semantic versioning for package releases.

| Change | Version level |
| ------ | ------------- |
| Raise the minimum PHP runtime, remove a public ruleset, rename a sniff code, or make a broad default profile stricter | Major |
| Add a new opt-in ruleset, add a new warning, add a new configurable sniff option, or add PHPCS 4 support without changing default findings | Minor |
| Fix false positives, fix false negatives in an existing rule without broad output churn, update docs, or widen compatible dependency ranges | Patch |

Every release should include a changelog entry with:

- Added, changed, deprecated, removed, fixed, and security sections where relevant.
- Compatibility impact for PHP, WordPress, PHPCS, WPCS, and VIPWPCS.
- Expected finding churn for existing projects.
- Any recommended migration command.

## CI Matrix

Every release candidate must be verified against the required matrix before tagging:

| Dimension | Required | Experimental |
| --------- | -------- | ------------ |
| PHP runtime | 8.2, 8.3, 8.4, 8.5 | next PHP minor when available |
| PHP_CodeSniffer | 3.x supported range | 4.x until promoted |
| WordPress target config | 6.5, 7.0 | latest trunk when useful |
| Standards | WPCS, VIPWPCS, Slevomat, PHPCompatibility | dependency pre-releases |

The repository-level GitHub workflow may live outside this package. The release notes must still
report the effective matrix used for the package release, including any skipped axis and the reason
it was skipped.

## Benchmark Protocol

Before promoting a new default rule or changing severity, run the profile against at least:

- A small plugin or package.
- A theme with PHP templates.
- A large legacy WordPress plugin.
- A WordPress VIP-oriented project if available.

Record:

- PHP version and PHPCS version.
- Ruleset profile.
- File count and approximate PHP lines.
- Runtime and peak memory.
- Total errors and warnings.
- Top 20 sniff codes by count.
- Known false positives and intentional suppressions.

Benchmarks are not pass/fail gates by themselves. They are evidence for whether a rule belongs in `Error`, `Warning`, or opt-in advisory status.

## Community Files

The SymPress organization `.github` repository provides default `CONTRIBUTING.md`, `SECURITY.md`, `CODE_OF_CONDUCT.md`, issue templates, pull request template, and CODEOWNERS. Add local overrides only when this package needs behavior that differs from the organization default.
