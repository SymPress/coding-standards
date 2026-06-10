# SymPress Coding Standards

> SymPress PHP coding standards for WordPress projects built with Symfony-level application structure.

SymPress Coding Standards is a PHP_CodeSniffer ruleset package for modern WordPress projects. It combines custom SymPress sniffs with established PHPCS standards for PHP, WordPress, WordPress VIP, PHP compatibility, variable analysis, array normalization, and modern PHP practices.

The package is designed for projects that keep domain and application code framework-agnostic while isolating WordPress integration at explicit boundaries.

## Table of Contents

- [Requirements](#requirements)
- [Installation](#installation)
  - [Local Installation](#local-installation)
  - [Global Installation](#global-installation)
- [Verify Installation](#verify-installation)
- [Standards Basis](#standards-basis)
- [Rulesets](#rulesets)
- [Enterprise Profiles](#enterprise-profiles)
- [Usage](#usage)
  - [Command Line](#command-line)
  - [Custom Ruleset](#custom-ruleset)
  - [Customization](#customization)
  - [Auto-Fixing](#auto-fixing)
  - [Disabling or Excluding Rules](#disabling-or-excluding-rules)
  - [IDE Integration](#ide-integration)
- [Enterprise Documentation](#enterprise-documentation)
- [Development](#development)
- [Copyright and License](#copyright-and-license)

## Requirements

The SymPress Coding Standards package requires:

- PHP 8.4 or newer to run the standard
- Composer 2
- PHP_CodeSniffer 3.13.5 or newer on the 3.x line
- PHPCompatibility 9.3 or 10
- PHPCSExtra
- PHPCSUtils
- Slevomat Coding Standard
- VariableAnalysis
- WordPress Coding Standards 3.1 or newer
- WordPress VIP Coding Standards

The enterprise profiles make the target PHP and WordPress support level explicit. Run PHP_CodeSniffer on a PHP runtime that can tokenize the syntax used by the project being checked.

When installed for local development, this package also declares PHPUnit and PHPStan-related development dependencies.

## Installation

Installing this package with Composer installs the required PHPCS standards and registers them through the PHP_CodeSniffer Standards Composer Installer plugin.

### Local Installation

Install the package in a project:

```bash
composer config allow-plugins.dealerdirect/phpcodesniffer-composer-installer true
composer require --dev sympress/coding-standards
```

### Global Installation

The package can also be installed globally:

```bash
composer global config allow-plugins.dealerdirect/phpcodesniffer-composer-installer true
composer global require --dev sympress/coding-standards
```

### Verify Installation

Verify that PHP_CodeSniffer discovered the standards:

```bash
vendor/bin/phpcs -i
```

The output should include the SymPress standards:

```text
SymPress, SymPress-Boundary, SymPress-Core, SymPress-Enterprise-LTS, SymPress-Enterprise-Modern, SymPress-Enterprise-Next, SymPress-Extra, SymPress-Plugin, SymPress-Pure, SymPress-Templates, SymPress-WordPress
```

## Standards Basis

SymPress Coding Standards is intentionally not a fully custom style dialect. It starts from
official and widely adopted PHP_CodeSniffer standards, then adds SymPress-specific rules where
WordPress projects with a Symfony-style application structure need sharper boundaries.

The formatting baseline follows [PSR-12](https://www.php-fig.org/psr/psr-12/) and the PSR rules
bundled with PHP_CodeSniffer. Import layout follows the PSR-12 header model: class imports,
function imports, and constant imports are separate blocks, with a single blank line between
blocks.

The pure PHP layer also uses Slevomat Coding Standard for modern PHP practices. Fully qualified
global function calls are normalized to explicit function imports. This keeps global function
references explicit and compiler-friendly while avoiding leading backslashes throughout
implementation code.

WordPress-facing profiles build on established WordPress ecosystem standards: WordPress Coding
Standards, selected WordPress VIP checks, PHPCompatibility, PHPCSExtra, PHPCSUtils, and
VariableAnalysis. SymPress rules sit on top of those foundations to express package boundaries,
architecture preferences, and project-level migration posture.

## Rulesets

This package contains these rulesets:

- `SymPress`: Complete set of custom sniffs defined by this package. Use it for cherry-picking specific SymPress rules.
- `SymPress-Pure`: Framework-agnostic PHP layer for domain, application, package, and infrastructure code.
- `SymPress-WordPress`: Extends `SymPress-Pure` with WordPress security, database, hook, i18n,
  compatibility, selected VIP checks, and WordPress i18n line-length exceptions. This layer does not pin a target PHP or WordPress version by itself.
- `SymPress-Boundary`: WordPress boundary layer for plugin files, theme functions, bootstrap scripts, and integration glue.
- `SymPress-Enterprise-LTS`: Conservative enterprise profile for WordPress 6.5+ migrations.
- `SymPress-Enterprise-Modern`: Recommended enterprise default for PHP 8.4+ and WordPress 7.0+ projects.
- `SymPress-Enterprise-Next`: Strict profile for new codebases on PHP 8.5+ and WordPress 7.0+.
- `SymPress-Plugin`: Compatibility name for `SymPress-Enterprise-Next`.
- `SymPress-Core`: Compatibility name for `SymPress-Enterprise-Next`.
- `SymPress-Extra`: Compatibility name for `SymPress-Enterprise-Next`.
- `SymPress-Templates`: WordPress security layer plus additional rules for PHP template files.

The `SymPress-Pure` layer is intentionally free of WordPress globals. Use `SymPress-Boundary` only where WordPress requires procedural entrypoints or direct integration code.

WordPress-specific allowances stay outside the pure layer. For example, `SymPress-WordPress`
configures `SymPress.Files.LineLength` to tolerate long literal strings passed to WordPress i18n
functions, while `SymPress-Pure` keeps the line-length rule framework-neutral.

Base layers such as `SymPress-Pure`, `SymPress-WordPress`, `SymPress-Boundary`, and `SymPress-Templates` are version-neutral. Use an enterprise profile or project-level `testVersion` and `minimum_wp_version` settings to define compatibility.

## Enterprise Profiles

New enterprise projects should choose an explicit compatibility profile:

| Profile | Use when | Target |
| ------- | -------- | ------ |
| `SymPress-Enterprise-LTS` | A large legacy codebase needs conservative rollout and warning-first architecture rules. | WordPress 6.5+ |
| `SymPress-Enterprise-Modern` | A current enterprise project wants strict security and correctness without forcing every style preference immediately. | PHP 8.4+, WordPress 7.0+ |
| `SymPress-Enterprise-Next` | A new package or greenfield project intentionally tracks the newest supported stack. | PHP 8.5+, WordPress 7.0+ |

See [docs/Compatibility.md](docs/Compatibility.md), [docs/Adoption.md](docs/Adoption.md), and [docs/Rules.md](docs/Rules.md) for the full strategy.

## Usage

### Command Line

Run PHP_CodeSniffer with a standard directly:

```bash
vendor/bin/phpcs --standard=SymPress-Plugin ./packages/my-plugin/src
```

Run the recommended enterprise default:

```bash
vendor/bin/phpcs --standard=SymPress-Enterprise-Modern ./packages/my-plugin/src
```

Run only the framework-agnostic layer:

```bash
vendor/bin/phpcs --standard=SymPress-Pure ./packages/my-library/src
```

Run template rules for a specific file. Prefer a project ruleset so the compatibility target is explicit:

```bash
vendor/bin/phpcs --standard=phpcs.xml.dist ./packages/my-theme/views/page.php
```

Use `-s` to display sniff codes:

```bash
vendor/bin/phpcs -s --standard=SymPress-Plugin ./packages/my-plugin/src
```

### Custom Ruleset

Like any PHP_CodeSniffer standard, SymPress can be referenced from a `phpcs.xml.dist` file.

A minimal configuration:

```xml
<?xml version="1.0"?>
<ruleset name="Project Coding Standard">
    <file>src</file>
    <file>tests</file>

    <rule ref="SymPress-Enterprise-Modern" />
</ruleset>
```

A fuller project configuration with boundary and template layers:

```xml
<?xml version="1.0"?>
<ruleset name="Project Coding Standard">
    <description>Project coding standard for an enterprise SymPress package.</description>
    <config name="text_domain" value="my-project" />

    <arg name="colors" />
    <arg value="sp" />

    <file>src</file>
    <file>tests</file>
    <file>templates</file>
    <file>plugin.php</file>

    <exclude-pattern>vendor/*</exclude-pattern>
    <exclude-pattern>node_modules/*</exclude-pattern>
    <exclude-pattern>build/*</exclude-pattern>

    <rule ref="SymPress-Enterprise-Modern">
        <exclude-pattern>*/plugin.php</exclude-pattern>
        <exclude-pattern>*/templates/*</exclude-pattern>
    </rule>

    <rule ref="SymPress-Boundary">
        <include-pattern>*/plugin.php</include-pattern>
        <include-pattern>*/functions.php</include-pattern>
        <include-pattern>*/config/*</include-pattern>
    </rule>

    <rule ref="SymPress-Templates">
        <include-pattern>*/templates/*</include-pattern>
        <include-pattern>*/views/*</include-pattern>
    </rule>

    <rule ref="SymPress.Functions.FunctionLength">
        <exclude-pattern>*/tests/*</exclude-pattern>
    </rule>
</ruleset>
```

Using a default ruleset keeps command-line usage simple:

```bash
vendor/bin/phpcs
```

Any command-line option passed to `phpcs` overrides the matching value from the ruleset.

### Customization

SymPress sniffs can be configured with PHPCS properties where the sniff exposes public configuration.

Example: change the maximum function length:

```xml
<rule ref="SymPress.Functions.FunctionLength">
    <properties>
        <property name="maxLength" type="integer" value="80" />
    </properties>
</rule>
```

Example: configure PSR-4 checks:

```xml
<rule ref="SymPress.Namespaces.Psr4">
    <properties>
        <property name="psr4" type="array">
            <element key="Acme\Plugin" value="src" />
            <element key="Acme\Plugin\Tests" value="tests" />
        </property>
    </properties>
</rule>
```

Example: allow additional short variable or parameter names:

```xml
<rule ref="SymPress.NamingConventions.ElementNameMinimalLength">
    <properties>
        <property name="additionalAllowedNames" type="array">
            <element value="db" />
            <element value="id" />
        </property>
    </properties>
</rule>
```

SymPress expects data accessors to use framework-friendly getter and setter names. Prefer:

```php
public function getTitle(): string
{
    return $this->title;
}

public function setTitle(string $title): void
{
    $this->title = $title;
}
```

Instead of property-style reads or behavior-like names for direct data assignment:

```php
public function title(): string
{
    return $this->title;
}

public function rename(string $title): void
{
    $this->title = $title;
}
```

The rule is exposed as `SymPress.Classes.AccessorNaming`. For concrete methods, it reports only simple
direct accessors such as `return $this->title;` or `$this->title = $title;` with no additional statements.
For abstract/interface signatures without a body, it falls back to property-like read names and setter-like
command prefixes. Boolean reads may use predicate getter names such as `isActive()` and `hasChildren()`.
PHP property hooks using `get` and `set` blocks are supported and are treated as property syntax, not method
accessor violations.

SymPress also reports redundant temporary variables that are assigned only to be used by a following `return`, `throw`, `echo`, `print`, or `yield` statement. Prefer:

```php
return (array) json_decode($json, true, 512, JSON_THROW_ON_ERROR);
```

Instead of:

```php
$decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

return (array) $decoded;
```

The rule is exposed as `SymPress.Variables.RedundantAssignment` and complements `SlevomatCodingStandard.Variables.UselessVariable`. It is intentionally aggressive: it also reports assignments made before unrelated business code and assignments whose right-hand side is a method or function call when the assigned variable is only used by the final statement.

```php
$foo = $this->buildValue();

$prefix = strtoupper('Result');

return sprintf('%s', $foo);
```

See [docs/Sniffs.md](docs/Sniffs.md) for the custom SymPress sniff list.

### Auto-Fixing

Some rules can be fixed automatically with PHP_CodeSniffer's fixer:

```bash
vendor/bin/phpcbf --standard=SymPress-Enterprise-Modern ./packages/my-plugin/src
```

Custom fixer output is covered by regression tests. Use automatic fixing deliberately and review the diff afterwards.

### Disabling or Excluding Rules

Prefer ruleset-level exclusions over inline comments. This keeps intentional deviations visible in review.

Disable a rule for the whole project:

```xml
<rule ref="SymPress-Enterprise-Modern">
    <exclude name="SymPress.ControlStructures.DisallowElse.ElseFound" />
</rule>
```

Disable a rule for a path:

```xml
<rule ref="SymPress.Functions.FunctionLength">
    <exclude-pattern>*/tests/*</exclude-pattern>
</rule>
```

Use inline ignores only for narrow, local exceptions:

```php
// phpcs:ignore SymPress.Functions.DisallowCallUserFunc.Found
call_user_func($callback);
```

More examples are available in [docs/Disabling.md](docs/Disabling.md).

### IDE Integration

For PhpStorm, configure PHP_CodeSniffer under:

```text
Settings -> PHP -> Quality Tools -> PHP_CodeSniffer
```

Point the tool to `vendor/bin/phpcs`, validate the path, then enable:

```text
Editor -> Inspections -> PHP -> Quality Tools -> PHP_CodeSniffer validation
```

Refresh the coding standard list and select `SymPress-Enterprise-Modern`, `SymPress-Plugin`, `SymPress-Pure`, or a project-specific `phpcs.xml.dist` file.

## Enterprise Documentation

- [Compatibility](docs/Compatibility.md) describes PHP, WordPress, VIP, PHPCompatibility, and PHPCS 4 posture.
- [Enterprise Adoption](docs/Adoption.md) describes baseline, strict-new-code-only, legacy-boundary, and full-strict rollout modes.
- [Rule Strategy](docs/Rules.md) defines severity classes and the custom sniff catalog.
- [Release and Trust Policy](docs/Release.md) defines SemVer, changelog expectations, CI matrix, and benchmark protocol.

SymPress uses the global [`SymPress/.github`](https://github.com/SymPress/.github/) repository for organization-wide contributing, security, code of conduct, issue, and pull request defaults. This package adds local docs only where the coding standard needs package-specific policy.

## Development

Run the package coding standard:

```bash
composer cs
```

Run PHPUnit:

```bash
composer tests
```

Run PHPUnit with coverage reports when Xdebug or PCOV is available:

```bash
composer tests:coverage
```

Run the full QA script:

```bash
composer qa
```

## Copyright and License

This package is distributed under GPL-2.0-or-later. It contains modified GPL-licensed work from the Syde PHP Coding Standards project. See [NOTICE.md](NOTICE.md) and [LICENSE](LICENSE).
