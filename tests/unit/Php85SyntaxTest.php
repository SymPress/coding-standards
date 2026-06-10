<?php

declare(strict_types=1);

namespace SymPressCS\SymPress\Tests;

use PHPUnit\Framework\Attributes\Test;
use SymPressCS\SymPress\Helpers\Names;

final class Php85SyntaxTest extends TestCase
{
    private const CODE = <<<'PHP'
<?php

declare(strict_types=1);

namespace SymPressCS\Fixture;

use Closure;
use Deprecated;
use NoDiscard;

#[Deprecated]
const PHP85_SYNTAX = strlen(...);

#[NoDiscard]
function transformed_length(string $value): int
{
    return $value
        |> trim(...)
        |> strlen(...);
}

final class Php85Syntax
{
    #[Deprecated]
    public const LENGTH = strlen(...);

    public function __construct(final protected string $value)
    {
    }

    public function callback(): Closure
    {
        return self::LENGTH;
    }
}

PHP;

    #[Test]
    public function php85PipeTokenIsClassifiedAsOperator(): void
    {
        $file = $this->factoryFile(self::CODE, '8.5');
        $tokens = $file->getTokens();

        $pipe = $file->findNext(T_PIPE, 0);

        self::assertIsInt($pipe);
        self::assertSame('|>', $tokens[$pipe]['content']);
        self::assertSame('operator', Names::tokenTypeName($file, $pipe));
    }

    #[Test]
    public function sympressPureParsesPhp85SyntaxWithoutSyntaxErrors(): void
    {
        $tempDir = sys_get_temp_dir() . '/sympress-cs-php85-' . bin2hex(random_bytes(8));
        self::assertTrue(mkdir($tempDir));

        $file = "{$tempDir}/Php85Syntax.php";
        file_put_contents($file, self::CODE);

        $libPath = rtrim((string) getenv('LIB_PATH'), '/');
        $command = sprintf(
            '%s --standard=%s --runtime-set testVersion 8.5- %s 2>&1',
            escapeshellarg("{$libPath}/vendor/bin/phpcs"),
            escapeshellarg("{$libPath}/SymPress-Pure"),
            escapeshellarg($file),
        );

        $output = [];
        $exitCode = 0;
        exec($command, $output, $exitCode);
        $message = implode(PHP_EOL, $output);

        try {
            self::assertSame(0, $exitCode, $message);
            self::assertStringNotContainsString('PHP syntax error', $message);
        } finally {
            unlink($file);
            rmdir($tempDir);
        }
    }
}
