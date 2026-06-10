<?php

declare(strict_types=1);

namespace SymPressCS\SymPress\Sniffs\WhiteSpace;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

use function strlen;

/** Multi Line Array sniff */
class ConstantSpacingSniff implements Sniff
{
    /**
     * Registers the tokens that this sniff wants to listen for.
     *
     * @return array<int, int>
     */
    public function register(): array
    {
        return [T_CONST];
    }

    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint
     */
    public function process(File $phpcsFile, $stackPtr): void
    {
        $tokens  = $phpcsFile->getTokens();
        $nextPtr = $stackPtr + 1;
        $next    = $tokens[$nextPtr]['content'];

        if ($tokens[$nextPtr]['code'] !== T_WHITESPACE) {
            return;
        }

        if ($next === ' ') {
            return;
        }

        $fix = $phpcsFile->addFixableError(
            'Keyword const must be followed by a single space, but found %s',
            $stackPtr,
            'Incorrect',
            [strlen($next)],
        );

        if ($fix !== true) {
            return;
        }

        $phpcsFile->fixer->replaceToken($nextPtr, ' ');
    }
}
