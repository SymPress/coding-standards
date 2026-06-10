<?php

declare(strict_types=1);

namespace SymPressCS\SymPress\Sniffs\Arrays;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

use function sprintf;

/** Multi Line Array sniff */
class MultiLineArraySniff implements Sniff
{
    /**
     * Registers the tokens that this sniff wants to listen for.
     *
     * @return array<int, int|string>
     */
    public function register(): array
    {
        return [
            // @phan-suppress-next-line PhanUndeclaredConstant
            T_OPEN_SHORT_ARRAY,
            T_ARRAY,
        ];
    }

    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param int  $stackPtr  The position of the current token in
     *                        the stack passed in $tokens.
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint
     */
    public function process(File $phpcsFile, $stackPtr): void
    {
        $tokens  = $phpcsFile->getTokens();
        $current = $tokens[$stackPtr];

        if ($current['code'] === T_ARRAY) {
            $arrayType = 'parenthesis';
            $start     = $current['parenthesis_opener'];
            $end       = $current['parenthesis_closer'];

            $this->processArray($phpcsFile, $arrayType, (int) $start, (int) $end);

            return;
        }

        $arrayType = 'bracket';
        $start     = $current['bracket_opener'];
        $end       = $current['bracket_closer'];

        $this->processArray($phpcsFile, $arrayType, (int) $start, (int) $end);
    }

    private function processArray(File $phpcsFile, string $arrayType, int $start, int $end): void
    {
        $tokens = $phpcsFile->getTokens();

        if ($tokens[$start]['line'] === $tokens[$end]['line']) {
            return;
        }

        if ($tokens[$start + 2]['line'] === $tokens[$start]['line']) {
            $fixable = $phpcsFile->addFixableError(
                sprintf(
                    'opening %s of multi line array must be followed by newline',
                    $arrayType,
                ),
                $start,
                'OpeningMustBeFollowedByNewline',
            );

            if ($fixable === true) {
                $phpcsFile->fixer->beginChangeset();
                $phpcsFile->fixer->addNewline($start);
                $phpcsFile->fixer->endChangeset();
            }
        }

        if ($tokens[$end - 2]['line'] !== $tokens[$end]['line']) {
            return;
        }

        $fixable = $phpcsFile->addFixableError(
            sprintf(
                'closing %s of multi line array must in own line',
                $arrayType,
            ),
            $end,
            'ClosingMustBeInOwnLine',
        );

        if ($fixable !== true) {
            return;
        }

        $phpcsFile->fixer->beginChangeset();
        $phpcsFile->fixer->addNewlineBefore($end);
        $phpcsFile->fixer->endChangeset();
    }
}
