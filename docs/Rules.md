# Rule Strategy

SymPress rules are grouped by enforcement intent. Enterprise projects should be able to tell whether a finding is a risk, a maintainability guardrail, or a team-style preference.

## Standards Foundation

SymPress uses established standards as the default source of truth before adding project-specific
rules. Formatting and file layout are based on PSR-12. WordPress-specific profiles reuse WordPress
Coding Standards, selected WordPress VIP checks, PHPCompatibility, PHPCSExtra, PHPCSUtils, Slevomat
Coding Standard, and VariableAnalysis.

Custom SymPress sniffs should therefore fill a gap that those standards do not cover, or tune the
combined rule set for SymPress package boundaries. They should not duplicate a vendor rule unless
the local behavior is intentionally different and documented.

Global PHP functions should be imported with `use function` when they are referenced explicitly.
This follows the PSR-12 import block structure and keeps calls compiler-friendly without relying on
leading-backslash calls in implementation code.

The WordPress profile should stay strict for platform, security, date/time, serialization, and
filesystem rules. Project-specific exceptions belong in a project ruleset or a narrower boundary
profile, not in the shared enterprise default.

## Severity Classes

| Class | Meaning | CI posture |
| ----- | ------- | ---------- |
| Risk | Security, compatibility, syntax, platform safety, or likely runtime failure | Error |
| Correctness | Code that is usually wrong or misleading but may need review | Error or warning by profile |
| Maintainability | Complexity, readability, dead code, and refactoring pressure | Warning in adoption profiles, stricter in full strict |
| Architecture | SymPress design constraints such as boundaries, globals, accessors, and object shape | Warning during adoption, error in strict profiles |
| Formatting | Mechanical layout and token style | Fix where possible, warning if noisy |

`SymPress-Enterprise-LTS` demotes the most subjective architecture and style rules to warnings. `SymPress-Enterprise-Modern` keeps the security and correctness layer strict while leaving selected architectural taste rules as warnings. `SymPress-Enterprise-Next` is the strictest profile.

## Custom Sniff Catalog

| Sniff | Class | Default posture | Notes |
| ----- | ----- | --------------- | ----- |
| `SymPress.Arrays.ArrayDoubleArrowAlignment` | Formatting | Error, fixable where safe | Normalizes multiline array alignment. |
| `SymPress.Arrays.MultiLineArray` | Formatting | Error, fixable where safe | Keeps multiline arrays stable in diffs. |
| `SymPress.Classes.AccessorNaming` | Architecture | Warning in adoption, strict in Next | Opinionated. Tune or demote when a domain model intentionally avoids getter/setter naming. |
| `SymPress.Classes.DeprecatedSerializableInterface` | Risk | Error | Blocks deprecated serialization APIs. |
| `SymPress.Classes.DeprecatedSerializeMagicMethod` | Risk | Error | Blocks deprecated magic serialization APIs. |
| `SymPress.Classes.PropertyLimit` | Architecture | Warning in enterprise profiles | Signals large objects. Treat as design pressure, not proof of a bug. |
| `SymPress.Complexity.NestingLevel` | Maintainability | Warning, then error at higher nesting | Thresholds are configurable. |
| `SymPress.ControlStructures.AlternativeSyntax` | Template formatting | Warning | Applies to template syntax rules. |
| `SymPress.ControlStructures.DisallowElse` | Architecture | Warning in enterprise profiles | Encourages early returns. Exclude when legacy diff churn is too high. |
| `SymPress.Encoding.Utf8EncodingComment` | Formatting | Warning | Keeps source encoding comments consistent. |
| `SymPress.Files.LineLength` | Maintainability | Warning | WordPress i18n functions are allowed in the WordPress layer. |
| `SymPress.Formatting.AlphabeticalUseStatements` | Formatting | Error, fixable | Mechanical import ordering. |
| `SymPress.Formatting.TrailingSemicolon` | Template formatting | Error, fixable | Template shorthand output cleanup. |
| `SymPress.Formatting.UnnecessaryNamespaceUsage` | Formatting | Warning, fixable where safe | Removes redundant namespace references. |
| `SymPress.Functions.ArgumentTypeDeclaration` | Correctness | Warning | Configurable allowed method names for framework signatures. |
| `SymPress.Functions.DisallowCallUserFunc` | Correctness | Error | Prefer direct callables unless dynamic dispatch is required. |
| `SymPress.Functions.DisallowGlobalFunction` | Architecture | Error outside boundary layer | WordPress entrypoints belong in `SymPress-Boundary`. |
| `SymPress.Functions.FunctionBodyStart` | Formatting | Warning | Enforces body spacing consistency. |
| `SymPress.Functions.FunctionLength` | Maintainability | Warning in enterprise profiles | Review threshold before blocking legacy packages. |
| `SymPress.Functions.ReturnTypeDeclaration` | Correctness | Warning plus specific errors | Catches missing and incompatible return types. |
| `SymPress.Functions.StaticClosure` | Correctness | Error, fixable | Avoids accidental `$this` binding. |
| `SymPress.Namespaces.Psr4` | Correctness | Error | Requires project-specific `psr4` mapping for useful checks. |
| `SymPress.NamingConventions.ElementNameMinimalLength` | Maintainability | Warning | Extend allowed names for domain terms such as `id` or `db`. |
| `SymPress.NamingConventions.VariableName` | Formatting | Warning | Supports camelCase or snake_case project policy. |
| `SymPress.PHP.DisallowShortOpenTag` | Risk | Error | Blocks non-portable PHP tags. |
| `SymPress.PHP.DisallowTopLevelDefine` | Architecture | Error outside boundary layer | Constants at entrypoints belong in `SymPress-Boundary`. |
| `SymPress.PHP.ShortOpenTagWithEcho` | Template formatting | Error, fixable | Converts verbose echo tags in templates. |
| `SymPress.Strings.VariableInDoubleQuotes` | Formatting | Warning | Prefer interpolation forms that are easier to scan. |
| `SymPress.Usage.IsNull` | Formatting | Warning, fixable | Prefer strict null comparison. |
| `SymPress.Variables.RedundantAssignment` | Maintainability | Warning in enterprise profiles | Intentionally aggressive. Suppress locally when a named value improves debugging or review. |
| `SymPress.WhiteSpace.ConstantSpacing` | Formatting | Warning | Keeps constants readable. |
| `SymPress.WhiteSpace.MultipleEmptyLines` | Formatting | Warning | Reduces diff noise. |
| `SymPress.WordPress.HookClosureReturn` | Risk | Error | Prevents return values from action callbacks and missing filter returns. |
| `SymPress.WordPress.HookPriority` | Maintainability | Warning | Encourages explicit hook priority. |

Vendor rules from WPCS, VIPWPCS, Slevomat, PHPCSExtra, PHPCSUtils, PHPCompatibility, and VariableAnalysis follow the same severity classes in project documentation and release notes.
