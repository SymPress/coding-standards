<?php

declare(strict_types=1);

namespace SymPressCS\SymPress\Sniffs\ControlStructures;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

final class DisallowElseSniff implements Sniff
{
    /** @return list<int|string> */
    public function register(): array
    {
        return [
            T_ELSE,
        ];
    }

    public function process(File $phpcsFile, $stackPtr): void
    {
        $phpcsFile->addWarning(
            'Do not use "%s"; use an early return statement instead',
            $stackPtr,
            'ElseFound',
            ['else'],
        );
    }
}
