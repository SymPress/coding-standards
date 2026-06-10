<?php

declare(strict_types=1);

namespace SymPressCS\SymPress\Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use PHP_CodeSniffer\Config;
use PHP_CodeSniffer\Files\DummyFile;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Ruleset;
use ReflectionClass;
use ReflectionException;

abstract class TestCase extends BaseTestCase
{
    /**
     * @param string $content
     * @param string|null $minTestVersion
     * @throws ReflectionException
     */
    protected function factoryFile(string $content, ?string $minTestVersion = null): File
    {
        $args = $minTestVersion === null
            ? []
            : ['--runtime-set', 'testVersion', "{$minTestVersion}-"];

        $config             = new Config($args, false);
        $config->standards  = [];
        $config->extensions = ['php' => 'PHP'];
        $config->setCommandLineValues([]);

        /** @var Ruleset $ruleset */
        $ruleset = (new ReflectionClass(Ruleset::class))->newInstanceWithoutConstructor();

        $file = new DummyFile($content, $ruleset, $config);
        $file->parse();

        return $file;
    }
}
