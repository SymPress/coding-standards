<?php

declare(strict_types=1);

namespace SymPressCS\SymPress\Sniffs\Functions;

use PHPCSUtils\Utils\FunctionDeclarations;
use PHPCSUtils\Utils\Namespaces;
use PHPCSUtils\Utils\Scopes;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

final class DisallowGlobalFunctionSniff implements Sniff
{
    /** @return list<int|string> */
    public function register(): array
    {
        return [
            T_FUNCTION,
        ];
    }

    public function process(File $phpcsFile, $stackPtr): void
    {
        if (Scopes::isOOMethod($phpcsFile, $stackPtr)) {
            return;
        }

        $namespace = Namespaces::determineNamespace($phpcsFile, $stackPtr);

        if ($namespace !== '') {
            return;
        }

        $name = FunctionDeclarations::getName($phpcsFile, $stackPtr);

        if (($name === null) || ($name === '')) {
            return;
        }

        $phpcsFile->addError(
            'Function "%s" found in global space; use namespaced function instead',
            $stackPtr,
            'Found',
            [$name],
        );
    }
}
