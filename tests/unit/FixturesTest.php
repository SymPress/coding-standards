<?php

declare(strict_types=1);

namespace SymPressCS\SymPress\Tests;

use Generator;
use PHPUnit\Framework\TestCase;
use PHP_CodeSniffer\Config;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Files\LocalFile;
use PHP_CodeSniffer\Ruleset;
use RuntimeException;
use SplStack;
use Throwable;

class FixturesTest extends TestCase
{
    /** @return Generator<string, array{string}, mixed, void> */
    public static function fixtureProvider(): Generator
    {
        $fixturesPath = rtrim((string) getenv('FIXTURES_PATH'), '/');

        $files = glob("{$fixturesPath}/*.php");
        if (!is_array($files)) {
            return;
        }

        foreach ($files as $file) {
            yield pathinfo($file, PATHINFO_FILENAME) => [$fixturesPath . '/' . pathinfo($file, PATHINFO_BASENAME)];
        }
    }

    /**
     * @test
     * @dataProvider fixtureProvider
     * @param string $fixtureFile
     */
    public function testAllFixtures(string $fixtureFile): void
    {
        /** @var SplStack<Throwable> $failures */
        $failures = new SplStack();

        $this->validateFixture(
            $fixtureFile,
            new FixtureContentParser(),
            $failures,
        );

        $previous = null;
        foreach ($failures as $failure) {
            if (!$failure instanceof Throwable) {
                continue;
            }

            $previous = new RuntimeException(
                $failure->getMessage(),
                (int) $failure->getCode(),
                $previous,
            );
        }

        if ($previous) {
            throw $previous;
        }
    }

    /**
     * @param string $fixtureFile
     * @param FixtureContentParser $parser
     * @param SplStack<Throwable> $failures
     */
    private function validateFixture(
        string $fixtureFile,
        FixtureContentParser $parser,
        SplStack $failures,
    ): void {

        try {
            /**
             * @var string $sniffClass
             * @var SniffMessages $expected
             * @var array<string, mixed> $properties
             */
            [$sniffClass, $expected, $properties] = $parser->parse($fixtureFile);

            $file = $this->createPhpcsForFixture($sniffClass, $fixtureFile, $properties);

            $actual = (new SniffMessagesExtractor($file))->extractMessages();
        } catch (Throwable $throwable) {
            $failures->push($throwable);
            return;
        }

        $fixtureBasename = basename($fixtureFile);

        $this->validateCodes($expected, $actual, $fixtureBasename, $sniffClass);
        $this->validateTotals($expected, $actual, $fixtureBasename, $sniffClass);
    }

    private function validateCodes(
        SniffMessages $expected,
        SniffMessages $actual,
        string $fixture,
        string $sniffClass,
    ): void {

        $where = sprintf(
            "\nin fixture file \"%s\" line %%d\nfor sniff \"%s\"",
            $fixture,
            $sniffClass,
        );

        foreach ($expected->getMessages() as $line => $codes) {
            $actualCodes = $actual->messageIn($line);
            foreach ($codes as $code) {
                $this->validateCode('message', $code, sprintf($where, $line), $actualCodes);
            }
        }

        foreach ($expected->getWarnings() as $line => $codes) {
            $actualCodes = $actual->warningIn($line);
            foreach ($codes as $code) {
                $this->validateCode('warning', $code, sprintf($where, $line), $actualCodes);
            }
        }

        foreach ($expected->getErrors() as $line => $codes) {
            $actualCodes = $actual->errorIn($line);
            foreach ($codes as $code) {
                $this->validateCode('error', $code, sprintf($where, $line), $actualCodes);
            }
        }
    }

    /** @param list<string> $actualCodes */
    private function validateCode(
        string $type,
        string $code,
        string $where,
        array $actualCodes = [],
    ): void {

        $message = $code
            ? sprintf('Expected %s code "%s" was not found', $type, $code)
            : sprintf('Expected %s was not found', $type);

        $code === ''
            ? static::assertNotSame([], $actualCodes, "{$message} {$where}.")
            : static::assertContains($code, $actualCodes, "{$message} {$where}.");
    }

    private function validateTotals(
        SniffMessages $expected,
        SniffMessages $actual,
        string $fixtureFile,
        string $sniffClass,
    ): void {

        $expectedTotal = $expected->getTotal();
        $actualTotal = $actual->getTotal();
        $unexpected = array_diff($actual->getMessageLines(), $expected->getMessageLines());
        $notRaised = array_diff($expected->getMessageLines(), $actual->getMessageLines());
        $mismatch = array_unique(array_merge($unexpected, $notRaised));

        self::assertSame(
            $expectedTotal,
            $actualTotal,
            sprintf(
                'Fixture file "%s" for sniff "%s" expected a total of %d messages, '
                . 'but found %d messages instead.'
                . ' (mismatch found at %s %s)',
                $fixtureFile,
                $sniffClass,
                $expectedTotal,
                $actualTotal,
                count($mismatch) === 1 ? 'line' : 'lines:',
                implode(', ', $mismatch),
            ),
        );
    }

    /**
     * @param string $sniffName
     * @param string $fixtureFile
     * @param array<string, mixed> $properties
     */
    private function createPhpcsForFixture(
        string $sniffName,
        string $fixtureFile,
        array $properties,
    ): File {

        $libPath = rtrim((string) getenv('LIB_PATH'), '/');

        $sniffFile = $this->buildSniffFile($sniffName);

        $sniffPath = "{$libPath}/{$sniffFile}.php";
        if (!is_readable($sniffPath)) {
            throw new RuntimeException(sprintf(
                'Sniff file "%s" does not exist or is not readable.',
                $sniffPath,
            ));
        }

        $standard = strtok($sniffName, '.');

        $config = new Config();
        $config->standards = ["{$libPath}/{$standard}"];
        $config->sniffs = [$sniffName];

        $ruleset = new Ruleset($config);

        $sniffFqn = 'SymPressCS\\' . str_replace('/', '\\', $sniffFile);
        foreach ($properties as $name => $value) {
            $ruleset->setSniffProperty(
                $sniffFqn,
                $name,
                ['scope' => 'sniff', 'value' => $value],
            );
        }

        return new LocalFile($fixtureFile, $ruleset, $config);
    }

    private function buildSniffFile(string $sniffName): string
    {
        $parts = explode('.', $sniffName);

        array_splice($parts, 1, 0, 'Sniffs');

        return implode('/', $parts) . 'Sniff';
    }
}
