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
