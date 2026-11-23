<?php

declare(strict_types=1);

namespace SymPressCS\SymPress\Tests;

use Generator;

final class FixerTest extends TestCase
{
    /** @return Generator<string, array{string, string, string}, mixed, void> */
    public static function fixerProvider(): Generator
    {
        yield 'short echo tag' => [
            'SymPress.PHP.ShortOpenTagWithEcho',
            '<?php echo $value; ?>',
            '<?= $value; ?>',
        ];

        yield 'trailing template semicolon' => [
            'SymPress.Formatting.TrailingSemicolon',
            '<?= $value; ?>',
            '<?= $value ?>',
        ];

        yield 'static closure' => [
            'SymPress.Functions.StaticClosure',
            <<<'PHP'
<?php
$callback = function (): string {
    return 'value';
};

PHP
            ,
            <<<'PHP'
<?php
$callback = static function (): string {
    return 'value';
};

PHP
            ,
        ];

        yield 'static closure keeps closures that include files' => [
            'SymPress.Functions.StaticClosure',
            <<<'PHP'
<?php
$render = function (string $file): void {
    include $file;
};

PHP
            ,
            <<<'PHP'
<?php
$render = function (string $file): void {
    include $file;
};

PHP
            ,
        ];

        yield 'is null comparison' => [
            'SymPress.Usage.IsNull',
            <<<'PHP'
<?php
return is_null($value);

PHP
            ,
            <<<'PHP'
<?php
return $value === null;

PHP
            ,
        ];

        yield 'variable in double quoted string' => [
            'SymPress.Strings.VariableInDoubleQuotes',
            <<<'PHP'
<?php
$message = "Hello $name";

PHP
            ,
            <<<'PHP'
<?php
$message = "Hello {$name}";

PHP
            ,
        ];

        foreach (self::isNullCases() as $name => [$input, $expected]) {
            yield "is null comparison: {$name}" => [
                'SymPress.Usage.IsNull',
                "<?php\n{$input}\n",
                "<?php\n{$expected}\n",
            ];
        }

        foreach (self::variableInDoubleQuotesCases() as $name => [$input, $expected]) {
            yield "variable in double quoted string: {$name}" => [
                'SymPress.Strings.VariableInDoubleQuotes',
                "<?php\n\$message = {$input};\n",
                "<?php\n\$message = {$expected};\n",
            ];
        }

        yield 'constant spacing' => [
            'SymPress.WhiteSpace.ConstantSpacing',
            <<<'PHP'
<?php
final class Example
{
    public const  VALUE = 'value';
}

PHP
            ,
            <<<'PHP'
<?php
final class Example
{
    public const VALUE = 'value';
}

PHP
            ,
        ];

        yield 'multiple empty lines' => [
            'SymPress.WhiteSpace.MultipleEmptyLines',
            <<<'PHP'
<?php
$first = true;


$second = true;

PHP
            ,
            <<<'PHP'
<?php
$first = true;

$second = true;

PHP
            ,
        ];

        yield 'alphabetical use statements' => [
            'SymPress.Formatting.AlphabeticalUseStatements',
            <<<'PHP'
<?php

namespace Example;

use Zeta\Last;
use Alpha\First;

PHP
            ,
            <<<'PHP'
<?php

namespace Example;

use Alpha\First;
use Zeta\Last;

PHP
            ,
        ];

        yield 'unnecessary namespace usage' => [
            'SymPress.Formatting.UnnecessaryNamespaceUsage',
            <<<'PHP'
<?php

namespace Example;

use Vendor\Service;

final class Consumer
{
    public function __construct(\Vendor\Service $service)
    {
    }
}

PHP
            ,
            <<<'PHP'
<?php

namespace Example;

use Vendor\Service;

final class Consumer
{
    public function __construct(Service $service)
    {
    }
}

PHP
            ,
        ];

        yield 'unnecessary namespace usage keeps names shadowed by imports' => [
            'SymPress.Formatting.UnnecessaryNamespaceUsage',
            <<<'PHP'
<?php

namespace Probe;

use Zeta\Thing;
use Zeta\Sub;
use Zeta\Other as Alias;

final class Consumer
{
    /** @param \Probe\Thing $thing */
    public function create(\Probe\Thing $thing): \Probe\Sub\Item
    {
        $alias = new \Probe\alias();
        $local = new \Probe\Local();

        return new \Probe\Sub\Item(\Zeta\Thing::class);
    }
}

PHP
            ,
            <<<'PHP'
<?php

namespace Probe;

use Zeta\Thing;
use Zeta\Sub;
use Zeta\Other as Alias;

final class Consumer
{
    /** @param \Probe\Thing $thing */
    public function create(\Probe\Thing $thing): \Probe\Sub\Item
    {
        $alias = new \Probe\alias();
        $local = new Local();

        return new \Probe\Sub\Item(Thing::class);
    }
}

PHP
            ,
        ];
    }

    /**
     * Statements before and after fixing. Unsafe contexts, where `=== null` would bind to
     * something else than the call result, are reported but left unchanged.
     *
     * @return array<string, array{string, string}>
     */
    private static function isNullCases(): array
    {
        return [
            'property access'                 => ['return is_null($obj->prop);', 'return $obj->prop === null;'],
            'static call'                     => ['return is_null(Foo::bar($x));', 'return Foo::bar($x) === null;'],
            'array offset'                    => ["return is_null(\$items['key']);", "return \$items['key'] === null;"],
            'fully qualified'                 => ['return \\is_null($value);', 'return $value === null;'],
            'unspaced coalesce'               => ['return is_null($a??$b);', 'return ($a??$b) === null;'],
            'negated unspaced and'            => ['return !is_null($a&&$b);', 'return ($a&&$b) !== null;'],
            'ternary argument'                => ['return is_null($a ? $b : $c);', 'return ($a ? $b : $c) === null;'],
            'spaced coalesce'                 => ['return is_null( $a ?? $b );', 'return ( $a ?? $b ) === null;'],
            'logical operand'                 => ['if (is_null($a) && $b) {}', 'if ($a === null && $b) {}'],
            'ternary condition'               => ['$x = is_null($a) ? 1 : 2;', '$x = $a === null ? 1 : 2;'],
            'call arguments'                  => ['foo(is_null($a), !is_null($b));', 'foo($a === null, $b !== null);'],
            'arrow function body'             => ['$f = fn ($a) => is_null($a);', '$f = fn ($a) => $a === null;'],
            'report only: concat after'       => ["\$x = is_null(\$a) . 'x';", "\$x = is_null(\$a) . 'x';"],
            'report only: concat before'      => ["\$x = 'x' . is_null(\$a);", "\$x = 'x' . is_null(\$a);"],
            'report only: cast'               => ['$x = (int) is_null($a);', '$x = (int) is_null($a);'],
            'report only: negated comparison' => ['return $a === !is_null($b);', 'return $a === !is_null($b);'],
        ];
    }

    /**
     * Double-quoted string literals before and after fixing; both must evaluate to the same value.
     *
     * @return array<string, array{string, string}>
     */
    private static function variableInDoubleQuotesCases(): array
    {
        return [
            'property access'                       => ['"Hello $user->name"', '"Hello {$user->name}"'],
            'nullsafe property access'              => ['"Hello $user?->name"', '"Hello {$user?->name}"'],
            'only first property is interpolated'   => ['"$user->name->first"', '"{$user->name}->first"'],
            'integer offset'                        => ['"Item $items[0]"', '"Item {$items[0]}"'],
            'negative integer offset'               => ['"Item $items[-1]"', '"Item {$items[-1]}"'],
            'unquoted string offset'                => ['"Item $items[key]"', '"Item {$items[\'key\']}"'],
            'variable offset'                       => ['"Item $items[$index]"', '"Item {$items[$index]}"'],
            'only first offset is interpolated'     => ['"$items[0][1]"', '"{$items[0]}[1]"'],
            'escaped dollar'                        => ['"\$total is $total"', '"\$total is {$total}"'],
            'escaped backslash before variable'     => ['"C:\\\\$path"', '"C:\\\\{$path}"'],
            'multiple variables'                    => [
                '"$first and {$second} and $third"',
                '"{$first} and {$second} and {$third}"',
            ],
            'multi-line string'                     => [
                "\"first \$first\nsecond \$user->name\"",
                "\"first {\$first}\nsecond {\$user->name}\"",
            ],
            'ambiguous offset is reported only'     => ['"Item $items[01]"', '"Item $items[01]"'],
            'negative zero offset is reported only' => ['"Item $items[-0]"', '"Item $items[-0]"'],
        ];
    }

    /**
     * @test
     * @dataProvider fixerProvider
     */
    public function testFixerOutput(string $sniff, string $input, string $expected): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'sympress-cs-fixer-');
        self::assertIsString($tempFile);

        $file = "{$tempFile}.php";
        file_put_contents($file, $input);

        $libPath = rtrim((string) getenv('LIB_PATH'), '/');
        $command = sprintf(
            '%s --standard=%s --sniffs=%s %s 2>&1',
            escapeshellarg("{$libPath}/vendor/bin/phpcbf"),
            escapeshellarg("{$libPath}/SymPress"),
            escapeshellarg($sniff),
            escapeshellarg($file),
        );

        $output = [];
        $exitCode = 0;
        exec($command, $output, $exitCode);

        try {
            self::assertContains($exitCode, [0, 1], implode(PHP_EOL, $output));
            self::assertSame($expected, (string) file_get_contents($file));
        } finally {
            unlink($file);
            unlink($tempFile);
        }
    }
}
