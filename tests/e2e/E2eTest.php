<?php

declare(strict_types=1);

namespace SymPressCS\SymPress\Tests;

use Generator;
use JsonException;

/** @psalm-type PhpcsMessagesData = array<string, list<array{source: string, line: int}>> */

class E2eTest extends TestCase
{
    private static string $phpCsBinary;
    private static string $testPackagePath;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        $libPath = (string) getenv('LIB_PATH');

        self::$phpCsBinary     = $libPath . '/vendor/bin/phpcs';
        self::$testPackagePath = $libPath . '/tests/e2e/test-package';
    }

    /** @return Generator<string, array{string}, mixed, void> */
    public static function publicRulesetProvider(): Generator
    {
        yield 'SymPress' => ['SymPress'];
        yield 'SymPress-Boundary' => ['SymPress-Boundary'];
        yield 'SymPress-Core' => ['SymPress-Core'];
        yield 'SymPress-Enterprise-LTS' => ['SymPress-Enterprise-LTS'];
        yield 'SymPress-Enterprise-Modern' => ['SymPress-Enterprise-Modern'];
        yield 'SymPress-Enterprise-Next' => ['SymPress-Enterprise-Next'];
        yield 'SymPress-Extra' => ['SymPress-Extra'];
        yield 'SymPress-Plugin' => ['SymPress-Plugin'];
        yield 'SymPress-Pure' => ['SymPress-Pure'];
        yield 'SymPress-Templates' => ['SymPress-Templates'];
        yield 'SymPress-WordPress' => ['SymPress-WordPress'];
    }

    /** @throws JsonException */
    public function testRulesets(): void
    {
        $output = [];

        // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.system_calls_exec
        exec(
            sprintf(
                'cd %s && %s',
                self::$testPackagePath,
                self::$phpCsBinary,
            ),
            $output,
        );

        $json = end($output);

        self::assertEquals($this->expectedMessages(), $this->actualMessages((string) $json));
    }

    /**
     * @dataProvider publicRulesetProvider
     * @throws JsonException
     */
    public function testPublicRulesetResolves(string $ruleset): void
    {
        $output = [];

        // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.system_calls_exec
        exec(
            sprintf(
                '%s --standard=%s --report=json --runtime-set text_domain sympress %s 2>&1',
                self::$phpCsBinary,
                escapeshellarg($ruleset),
                escapeshellarg(self::$testPackagePath . '/index.php'),
            ),
            $output,
            $exitCode,
        );

        self::assertContains($exitCode, [0, 1, 2], implode(PHP_EOL, $output));

        /** @var array{files?: array<string, mixed>} $data */
        $data = json_decode((string) end($output), true, 512, JSON_THROW_ON_ERROR);

        self::assertArrayHasKey('files', $data);
    }

    /**
     * @return array<string, list<array{source: string, line: int}>>
     * @throws JsonException
     */
    private function expectedMessages(): array
    {
        return $this->decodeMessages((string) file_get_contents(self::$testPackagePath . '/messages.json'));
    }

    /**
     * @param string $json
     * @return array<string, list<array{source: string, line: int}>>
     * @throws JsonException
     */
    private function actualMessages(string $json): array
    {
        /** @var array{files: array<string, PhpcsMessagesData>} $data */
        $data = (array) json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        $result = [];

        foreach ($data['files'] as $fileName => $fileData) {
            $baseName          = basename($fileName);
            $result[$baseName] = [];

            foreach ($fileData['messages'] as ['source' => $source, 'line' => $line]) {
                $result[$baseName][] = ['source' => $source, 'line' => $line];
            }
        }

        return $result;
    }

    /**
     * @param string $json
     * @return array<string, list<array{source: string, line: int}>>
     * @throws JsonException
     */
    private function decodeMessages(string $json): array
    {
        $messages = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        if (!is_array($messages)) {
            return [];
        }

        return $this->normalizeMessages($messages);
    }

    /**
     * @param array<mixed, mixed> $messages
     * @return array<string, list<array{source: string, line: int}>>
     */
    private function normalizeMessages(array $messages): array
    {
        $normalized = [];

        foreach ($messages as $fileName => $fileMessages) {
            if (!is_string($fileName) || !is_array($fileMessages)) {
                continue;
            }

            $normalized[$fileName] = [];

            foreach ($fileMessages as $message) {
                if (!is_array($message)) {
                    continue;
                }

                $source = $message['source'] ?? null;
                $line   = $message['line'] ?? null;

                if (!is_string($source) || !is_int($line)) {
                    continue;
                }

                $normalized[$fileName][] = ['source' => $source, 'line' => $line];
            }
        }

        return $normalized;
    }
}
