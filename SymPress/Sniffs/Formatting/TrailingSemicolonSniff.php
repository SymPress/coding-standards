<?php

declare(strict_types=1);

namespace SymPressCS\SymPress\Sniffs\Formatting;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;

use function is_int;

/**
 * @phpstan-type Token array{
 *     type: string,
 *     code: string|int,
 *     line: int,
 * }
 */
final class TrailingSemicolonSniff implements Sniff
{
    /** @return list<int|string> */
    public function register(): array
    {
        return [
            T_SEMICOLON,
        ];
    }

    public function process(File $phpcsFile, $stackPtr): void
    {
        $nextNonEmptyPosition = $phpcsFile->findNext(
            Tokens::$emptyTokens,
            $stackPtr + 1,
            null,
            true,
        );

        if (!is_int($nextNonEmptyPosition)) {
            return;
        }

        /** @var array<int, Token> $tokens */
        $tokens = $phpcsFile->getTokens();

        if (!isset($tokens[$nextNonEmptyPosition])) {
            return;
        }

        $nextNonEmptyToken = $tokens[$nextNonEmptyPosition];

        if ($nextNonEmptyToken['code'] !== T_CLOSE_TAG) {
            return;
        }

        $currentLine = $tokens[$stackPtr]['line'];

        if ($nextNonEmptyToken['line'] !== $currentLine) {
            return;
        }

        $message = 'Trailing semicolon found in line %d';

        if (!$phpcsFile->addFixableWarning($message, $stackPtr, 'Found', [$currentLine])) {
            return;
        }

        $this->fix($phpcsFile, $stackPtr);
    }

    private function fix(File $file, int $position): void
    {
        $fixer = $file->fixer;

        $fixer->beginChangeset();

        $fixer->replaceToken($position, '');

        $fixer->endChangeset();
    }
}
