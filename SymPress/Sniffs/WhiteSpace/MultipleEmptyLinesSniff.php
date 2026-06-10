<?php

declare(strict_types=1);

namespace SymPressCS\SymPress\Sniffs\WhiteSpace;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/** Check multiple consecutive newlines in a file */
class MultipleEmptyLinesSniff implements Sniff
{
    /**
     * Registers the tokens that this sniff wants to listen for.
     *
     * @return array<int, int>
     */
    public function register(): array
    {
        return [
            // Assume most comments end with a newline
            T_COMMENT,
            // Assume all <?php open tags end with a newline
            T_OPEN_TAG,
            T_WHITESPACE,
        ];
    }

    /**
     * @param int  $stackPtr  The current token index.
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint
     */
    public function process(File $phpcsFile, $stackPtr): ?int
    {
        $tokens = $phpcsFile->getTokens();

        // This sniff intentionally doesn't care about whitespace at the end of the file
        if (
            !isset($tokens[$stackPtr + 3])
            || $tokens[$stackPtr + 2]['line'] === $tokens[$stackPtr + 3]['line']
        ) {
            return $stackPtr + 3;
        }

        if ($tokens[$stackPtr + 1]['line'] === $tokens[$stackPtr + 2]['line']) {
            return $stackPtr + 2;
        }

        // Finally, check the assumption the current token is or ends with a newline
        if ($tokens[$stackPtr]['line'] === $tokens[$stackPtr + 1]['line']) {
            return null;
        }

        // Search for the next non-newline token
        $next = $stackPtr + 1;

        while (
            isset($tokens[$next + 1]) &&
            $tokens[$next]['code'] === T_WHITESPACE &&
            $tokens[$next]['line'] !== $tokens[$next + 1]['line']
        ) {
            $next++;
        }

        $count = $next - $stackPtr - 1;

        if (
            $count > 1
            && $phpcsFile->addFixableError(
                'Multiple empty lines should not exist in a row; found %s consecutive empty lines',
                $stackPtr + 1,
                'MultipleEmptyLines',
                [$count],
            )
        ) {
            $phpcsFile->fixer->beginChangeset();

            // Remove all newlines except the first two, i.e. keep one empty line
            for ($i = $stackPtr + 2; $i < $next; $i++) {
                $phpcsFile->fixer->replaceToken($i, '');
            }

            $phpcsFile->fixer->endChangeset();
        }

        // Don't check the current sequence a second time
        return $next;
    }
}
