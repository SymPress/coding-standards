# IDE Integration

## PhpStorm

Open the PHP_CodeSniffer configuration:

```text
Settings -> PHP -> Quality Tools -> PHP_CodeSniffer
```

Set the executable path to the project binary:

```text
vendor/bin/phpcs
```

Validate the path. Then enable PHP_CodeSniffer inspections:

```text
Editor -> Inspections -> PHP -> Quality Tools -> PHP_CodeSniffer validation
```

Refresh the coding standard list and select one of:

- `SymPress-Plugin`
- `SymPress-Enterprise-LTS`
- `SymPress-Enterprise-Modern`
- `SymPress-Enterprise-Next`
- `SymPress-Pure`
- `SymPress-WordPress`
- a project-specific `phpcs.xml.dist`

Use `SymPress-Enterprise-Modern` for the default enterprise profile. Use the project-specific `phpcs.xml.dist` when the project has boundary or template path rules.

## Command-Line Parity

The IDE should use the same binary and ruleset as CI:

```bash
vendor/bin/phpcs --standard=phpcs.xml.dist
```
