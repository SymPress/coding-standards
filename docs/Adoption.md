# Enterprise Adoption

Large WordPress codebases should adopt SymPress Coding Standards in stages. The goal is to make quality visible without turning the first rollout into a rewrite.

## Adoption Modes

### Baseline

Use this for an existing project with unknown quality state.

```xml
<rule ref="SymPress-Enterprise-LTS" />
<config name="ignore_warnings_on_exit" value="1" />
```

Security, compatibility, syntax, and high-confidence correctness findings remain visible. Architectural and style rules are warnings in this profile, so teams can review them without blocking every build.

### Strict New Code Only

Run the baseline profile on the whole repository and a stricter profile only on changed files.

```bash
vendor/bin/phpcs --standard=phpcs.xml.dist
git diff --name-only --diff-filter=ACMRT origin/main...HEAD -- '*.php' | xargs vendor/bin/phpcs --standard=SymPress-Enterprise-Modern
```

Use this when the team wants strict standards for new work while existing legacy code is still being paid down.

### Legacy Boundary

Use strict rules for application code and explicit boundary rules for WordPress entrypoints, theme `functions.php`, bootstrap files, config glue, and PHP templates.

```xml
<rule ref="SymPress-Enterprise-Modern">
    <exclude-pattern>*/plugin.php</exclude-pattern>
    <exclude-pattern>*/functions.php</exclude-pattern>
    <exclude-pattern>*/templates/*</exclude-pattern>
</rule>

<rule ref="SymPress-Boundary">
    <include-pattern>*/plugin.php</include-pattern>
    <include-pattern>*/functions.php</include-pattern>
</rule>

<rule ref="SymPress-Templates">
    <include-pattern>*/templates/*</include-pattern>
    <include-pattern>*/views/*</include-pattern>
</rule>
```

`SymPress-Templates` includes the WordPress security layer and then adds template syntax rules. Template files should not be excluded from escaping, i18n, nonce, or validated-input checks.

### Full Strict

Use this for new packages or mature codebases.

```xml
<rule ref="SymPress-Enterprise-Next" />
```

Teams may also use `SymPress-Enterprise-Modern` as the long-lived default and reserve `SymPress-Enterprise-Next` for projects that intentionally track the newest PHP and WordPress releases.

## False-Positive Policy

Handle false positives in this order:

1. Tune the rule with a ruleset property when the project has a repeatable convention.
2. Exclude the rule for a narrow path when the path has a known boundary or generated-code role.
3. Use an inline `phpcs:ignore` only for a local exception that cannot be expressed in the ruleset.
4. Report a sniff bug with a minimal fixture when the rule is wrong for ordinary code.

Inline ignores should include the full sniff code. Security rules should only be ignored with a clear reason, a small scope, and a compensating test or review note.

## Migration Checklist

- Pick the compatibility profile before changing any code.
- Run PHPCS with `-s` so every finding includes a sniff code.
- Record the first run as a baseline artifact in CI.
- Fix auto-fixable formatting issues separately from behavior-adjacent changes.
- Promote warning-only rules to blocking rules one category at a time.
- Keep boundary and template path rules explicit in `phpcs.xml.dist`.
