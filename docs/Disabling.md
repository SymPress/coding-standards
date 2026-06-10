# Disabling or Excluding Rules

Prefer ruleset-level exclusions over inline ignores. A rule disabled in `phpcs.xml.dist` is visible to reviewers and applies consistently across local development, CI, and IDE integrations.

## Disable a Rule Globally

```xml
<rule ref="SymPress-Enterprise-Modern">
    <exclude name="SymPress.ControlStructures.DisallowElse.ElseFound" />
</rule>
```

## Disable a Rule for a Path

```xml
<rule ref="SymPress.Functions.FunctionLength">
    <exclude-pattern>*/tests/*</exclude-pattern>
</rule>
```

## Apply a Different Standard to a Path

Exclude the path from the general standard and include it in a narrower standard:

```xml
<rule ref="SymPress-Enterprise-Modern">
    <exclude-pattern>*/templates/*</exclude-pattern>
</rule>

<rule ref="SymPress-Templates">
    <include-pattern>*/templates/*</include-pattern>
</rule>
```

`SymPress-Templates` includes the WordPress security layer. Use it as the template-specific profile, not as a way to opt templates out of escaping, i18n, nonce, or validated-input checks.

## Inline Exceptions

Use inline ignores only when the exception is intentionally tied to one small piece of code:

```php
// phpcs:ignore SymPress.Functions.DisallowCallUserFunc.Found
call_user_func($callback);
```

Use a block-level ignore for generated or framework-required code that cannot be expressed cleanly in a ruleset:

```php
// phpcs:disable SymPress.Functions.DisallowGlobalFunction.Found
function plugin_bootstrap(): void
{
    // WordPress entrypoint glue.
}
// phpcs:enable SymPress.Functions.DisallowGlobalFunction.Found
```

Keep inline suppressions as narrow as possible and include the full sniff code.

See [Enterprise Adoption](Adoption.md) for the full false-positive policy.
