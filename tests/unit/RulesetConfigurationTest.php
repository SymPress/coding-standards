<?php

declare(strict_types=1);

namespace SymPressCS\SymPress\Tests;

use Generator;
use PHPUnit\Framework\TestCase;

final class RulesetConfigurationTest extends TestCase
{
    /** @return Generator<string, array{string}, mixed, void> */
    public static function rulesetProvider(): Generator
    {
        $libPath = rtrim((string) getenv('LIB_PATH'), '/');

        foreach (glob("{$libPath}/SymPress-*/ruleset.xml") ?: [] as $ruleset) {
            yield basename(dirname($ruleset)) => [$ruleset];
        }

        yield 'package phpcs.xml' => ["{$libPath}/phpcs.xml"];
        yield 'e2e phpcs.xml' => ["{$libPath}/tests/e2e/test-package/phpcs.xml"];
    }

    /** @dataProvider rulesetProvider */
    public function testRulesetsUseCurrentWordPressMinimumVersionConfigName(string $ruleset): void
    {
        $contents = (string) file_get_contents($ruleset);

        self::assertStringNotContainsString('minimum_supported_wp_version', $contents);
    }

    /** @dataProvider rulesetProvider */
    public function testConfiguredWordPressMinimumUsesWpcsThreeConfigName(string $ruleset): void
    {
        $contents = (string) file_get_contents($ruleset);

        if (!str_contains($contents, 'minimum_wp_version')) {
            self::addToAssertionCount(1);
            return;
        }

        self::assertMatchesRegularExpression(
            '~<config\s+name="minimum_wp_version"\s+value="\d+\.\d+"\s*/>~',
            $contents,
        );
    }

    public function testCustomSniffCatalogsMatchImplementations(): void
    {
        $libPath = rtrim((string) getenv('LIB_PATH'), '/');
        $implemented = [];

        foreach (glob("{$libPath}/SymPress/Sniffs/*/*Sniff.php") ?: [] as $file) {
            $relative = substr($file, strlen("{$libPath}/SymPress/Sniffs/"));
            [$category, $class] = explode('/', $relative);
            $implemented[] = sprintf('SymPress.%s.%s', $category, substr($class, 0, -strlen('Sniff.php')));
        }

        sort($implemented);

        foreach (['docs/Sniffs.md', 'docs/Rules.md'] as $documentation) {
            preg_match_all(
                '/`(SymPress\.[A-Za-z0-9.]+)`/',
                (string) file_get_contents("{$libPath}/{$documentation}"),
                $matches,
            );
            $documented = $matches[1];
            sort($documented);

            self::assertSame(
                $implemented,
                $documented,
                "{$documentation} must list every implemented custom sniff once.",
            );
        }
    }
}
