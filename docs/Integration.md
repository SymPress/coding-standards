# Integration

## Composer

Install the package as a development dependency:

```bash
composer require --dev sympress/coding-standards
```

Composer must allow the PHP_CodeSniffer installer plugin:

```bash
composer config allow-plugins.dealerdirect/phpcodesniffer-composer-installer true
```

## Verify Installation

Run:

```bash
vendor/bin/phpcs -i
```

The output should include:

- `SymPress`
- `SymPress-Pure`
- `SymPress-WordPress`
- `SymPress-Boundary`
- `SymPress-Enterprise-LTS`
- `SymPress-Enterprise-Modern`
- `SymPress-Enterprise-Next`
- `SymPress-Plugin`
- `SymPress-Core`
- `SymPress-Extra`
- `SymPress-Templates`

## Choosing a Ruleset

Use `SymPress-Pure` for framework-agnostic code:

```xml
<rule ref="SymPress-Pure" />
```

When using a base layer directly, set the compatibility target in the project ruleset:

```xml
<config name="testVersion" value="8.4-" />
<config name="minimum_wp_version" value="7.0" />
```

Use `SymPress-Enterprise-Modern` for the default enterprise WordPress profile:

```xml
<rule ref="SymPress-Enterprise-Modern" />
```

Use `SymPress-Enterprise-LTS` for conservative legacy adoption and `SymPress-Enterprise-Next` for strict new codebases.

Use `SymPress-Boundary` for entrypoints and integration glue:

```xml
<rule ref="SymPress-Boundary">
    <include-pattern>*/plugin.php</include-pattern>
    <include-pattern>*/functions.php</include-pattern>
    <include-pattern>*/config/*</include-pattern>
</rule>
```

Use `SymPress-Templates` for PHP templates:

```xml
<rule ref="SymPress-Templates">
    <include-pattern>*/templates/*</include-pattern>
    <include-pattern>*/views/*</include-pattern>
</rule>
```

## Project Ruleset

Recommended package-level `phpcs.xml.dist`:

```xml
<?xml version="1.0"?>
<ruleset name="Package Coding Standard">
    <arg name="colors" />
    <arg value="sp" />

    <file>src</file>
    <file>tests</file>
    <file>templates</file>
    <file>plugin.php</file>

    <exclude-pattern>vendor/*</exclude-pattern>
    <exclude-pattern>node_modules/*</exclude-pattern>

    <rule ref="SymPress-Enterprise-Modern">
        <exclude-pattern>*/plugin.php</exclude-pattern>
        <exclude-pattern>*/templates/*</exclude-pattern>
    </rule>

    <rule ref="SymPress-Boundary">
        <include-pattern>*/plugin.php</include-pattern>
        <include-pattern>*/functions.php</include-pattern>
    </rule>

    <rule ref="SymPress-Templates">
        <include-pattern>*/templates/*</include-pattern>
    </rule>
</ruleset>
```

`SymPress-Templates` already includes the WordPress security layer. Do not replace it with a syntax-only template standard.

See [Adoption](Adoption.md) for staged rollout modes and [Compatibility](Compatibility.md) for profile targets.

## Composer Scripts

Recommended package scripts:

```json
{
    "scripts": {
        "cs": "phpcs --standard=phpcs.xml.dist",
        "cs:fix": "phpcbf --standard=phpcs.xml.dist"
    }
}
```
