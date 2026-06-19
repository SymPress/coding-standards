# Custom Sniffs

For severity classes, enterprise profile behavior, and false-positive guidance, see [Rules](Rules.md).

The package exposes these SymPress custom sniffs:

- `SymPress.Arrays.ArrayDoubleArrowAlignment`
- `SymPress.Arrays.MultiLineArray`
- `SymPress.Classes.AccessorNaming`
- `SymPress.Classes.DeprecatedSerializableInterface`
- `SymPress.Classes.DeprecatedSerializeMagicMethod`
- `SymPress.Classes.ClassLength`
- `SymPress.Classes.PropertyLimit`
- `SymPress.Complexity.NestingLevel`
- `SymPress.ControlStructures.AlternativeSyntax`
- `SymPress.ControlStructures.DisallowElse`
- `SymPress.Encoding.Utf8EncodingComment`
- `SymPress.Files.FileLength`
- `SymPress.Files.LineLength`
- `SymPress.Formatting.AlphabeticalUseStatements`
- `SymPress.Formatting.TrailingSemicolon`
- `SymPress.Formatting.UnnecessaryNamespaceUsage`
- `SymPress.Functions.ArgumentTypeDeclaration`
- `SymPress.Functions.DisallowCallUserFunc`
- `SymPress.Functions.DisallowGlobalFunction`
- `SymPress.Functions.FunctionBodyStart`
- `SymPress.Functions.FunctionLength`
- `SymPress.Functions.ReturnTypeDeclaration`
- `SymPress.Functions.StaticClosure`
- `SymPress.Namespaces.Psr4`
- `SymPress.NamingConventions.ElementNameMinimalLength`
- `SymPress.NamingConventions.VariableName`
- `SymPress.PHP.DisallowShortOpenTag`
- `SymPress.PHP.DisallowTopLevelDefine`
- `SymPress.PHP.ShortOpenTagWithEcho`
- `SymPress.Strings.VariableInDoubleQuotes`
- `SymPress.Usage.IsNull`
- `SymPress.Variables.RedundantAssignment`
- `SymPress.WhiteSpace.ConstantSpacing`
- `SymPress.WhiteSpace.MultipleEmptyLines`
- `SymPress.WordPress.HookClosureReturn`
- `SymPress.WordPress.HookPriority`

Run `phpcs -e --standard=SymPress` to see the active custom sniff list resolved by the installed package.
