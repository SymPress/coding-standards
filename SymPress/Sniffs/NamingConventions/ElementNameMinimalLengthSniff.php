<?php

declare(strict_types=1);

namespace SymPressCS\SymPress\Sniffs\NamingConventions;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use SymPressCS\SymPress\Helpers\Names;

use function is_string;
use function mb_strlen;
use function strtolower;

final class ElementNameMinimalLengthSniff implements Sniff
{
    public int $minLength = 3;

    /** @var list<string> */
    public array $allowedShortNames = [
        'an',
        'as',
        'at',
        'be',
        'by',
        'c',
        'db',
        'do',
        'ex',
        'go',
        'he',
        'hi',
        'i',
        'id',
        'if',
        'in',
        'io',
        'is',
        'it',
        'js',
        'me',
        'my',
        'no',
        'of',
        'ok',
        'on',
        'or',
        'pi',
        'sh',
        'so',
        'to',
        'up',
        'we',
        'wp',
    ];

    /** @var list<string> */
    public array $additionalAllowedNames = [];

    /** @return list<int|string> */
    public function register(): array
    {
        return Names::NAMEABLE_TOKENS;
    }

    public function process(File $phpcsFile, $stackPtr): void
    {
        $elementName = Names::nameableTokenName($phpcsFile, $stackPtr);

        if (!is_string($elementName)) {
            return;
        }

        $elementNameLength = mb_strlen($elementName);

        if (($elementNameLength >= $this->minLength) || $this->isShortNameAllowed($elementName)) {
            return;
        }

        $typeName = Names::tokenTypeName($phpcsFile, $stackPtr);

        $phpcsFile->addWarning(
            'Length of %s name "%s" (%d) is less than the recommended minimum of %d characters',
            $stackPtr,
            'TooShort',
            [$typeName, $elementName, $elementNameLength, $this->minLength],
        );
    }

    private function isShortNameAllowed(string $variableName): bool
    {
        $target = strtolower($variableName);

        foreach ($this->allowedShortNames as $allowed) {
            if (strtolower($allowed) === $target) {
                return true;
            }
        }

        foreach ($this->additionalAllowedNames as $allowed) {
            if (strtolower($allowed) === $target) {
                return true;
            }
        }

        return false;
    }
}
