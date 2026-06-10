<?php

declare(strict_types=1);

namespace SymPressCS\SymPress\Sniffs\Arrays;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens as PHP_CodeSniffer_Tokens;

use function array_pop;
use function in_array;
use function sprintf;
use function str_repeat;
use function strlen;

/**
 * Array Double Arrow Alignment sniff.
 *
 * '=>' must be aligned in arrays, and the key and the '=>' must be in the same line
 */
class ArrayDoubleArrowAlignmentSniff implements Sniff
{
    /**
     * Define all types of arrays.
     *
     * @var array<int, int|string> $arrayTokens
     */
    protected array $arrayTokens = [
        // @phan-suppress-next-line PhanUndeclaredConstant
        T_OPEN_SHORT_ARRAY,
        T_ARRAY,
    ];

    /**
     * Registers the tokens that this sniff wants to listen for.
     *
     * @return array<int, int|string>
     */
    public function register(): array
    {
        return $this->arrayTokens;
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
        $bounds  = $this->arrayBounds($current);

        $start = $bounds['start'];
        $end   = $bounds['end'];

        if ($tokens[$start]['line'] === $tokens[$end]['line']) {
            return;
        }

        $analysis = $this->collectAssignments($phpcsFile, $start, $end);

        $this->alignAssignments($phpcsFile, $analysis['assignments'], $analysis['doubleArrowStartColumn']);
    }

    /**
     * @param array<string, mixed> $token
     * @return array{start: int, end: int}
     */
    private function arrayBounds(array $token): array
    {
        if ($token['code'] === T_ARRAY) {
            return [
                'start' => (int) $token['parenthesis_opener'],
                'end'   => (int) $token['parenthesis_closer'],
            ];
        }

        return [
            'start' => (int) $token['bracket_opener'],
            'end'   => (int) $token['bracket_closer'],
        ];
    }

    /** @return array{assignments: list<int>, doubleArrowStartColumn: int} */
    private function collectAssignments(File $phpcsFile, int $start, int $end): array
    {
        /** @var list<int> $assignments */
        $assignments = [];

        $tokens       = $phpcsFile->getTokens();
        $keyEndColumn = -1;
        $lastLine     = -1;

        for ($i = $start + 1; $i < $end; $i++) {
            $current  = $tokens[$i];
            $previous = $tokens[$i - 1];

            // Skip nested arrays.
            if (in_array($current['code'], $this->arrayTokens, true) === true) {
                $bounds = $this->arrayBounds($current);
                $i      = $bounds['end'] + 1;

                continue;
            }

            // Skip closures in array.
            if ($current['code'] === T_CLOSURE) {
                $i = $current['scope_closer'] + 1;

                continue;
            }

            $i = (int) $i;

            if ($current['code'] !== T_DOUBLE_ARROW) {
                continue;
            }

            $assignments[] = $i;
            $column        = $previous['column'];
            $line          = $current['line'];

            if ($lastLine === $line) {
                $this->reportMultipleAssignmentsOnLine($phpcsFile, $i, $start, $assignments);
            }

            $this->checkKeyAndDoubleArrowInSameLine($phpcsFile, $i);

            if ($column > $keyEndColumn) {
                $keyEndColumn = $column;
            }

            $lastLine = $line;
        }

        return [
            'assignments'            => $assignments,
            'doubleArrowStartColumn' => $keyEndColumn + 1,
        ];
    }

    /** @param list<int> $assignments */
    private function reportMultipleAssignmentsOnLine(
        File $phpcsFile,
        int $stackPtr,
        int $start,
        array &$assignments,
    ): void {

        $previousComma = $this->getPreviousComma($phpcsFile, $stackPtr, $start);

        $msg = 'only one "=>" assignments per line is allowed in a multi line array';

        if ($previousComma === false) {
            // Remove current and previous '=>' from array for further processing.
            array_pop($assignments);
            array_pop($assignments);
            $phpcsFile->addError($msg, $stackPtr, 'OneAssignmentPerLine');

            return;
        }

        $fixable = $phpcsFile->addFixableError($msg, $stackPtr, 'OneAssignmentPerLine');

        if ($fixable !== true) {
            return;
        }

        $phpcsFile->fixer->beginChangeset();
        $phpcsFile->fixer->addNewline((int) $previousComma);
        $phpcsFile->fixer->endChangeset();
    }

    private function checkKeyAndDoubleArrowInSameLine(File $phpcsFile, int $stackPtr): void
    {
        $tokens  = $phpcsFile->getTokens();
        $current = $tokens[$stackPtr];
        $index   = $stackPtr - 1;

        while ($index >= 0 && $tokens[$index]['line'] === $current['line']) {
            if (in_array($tokens[$index]['code'], PHP_CodeSniffer_Tokens::$emptyTokens, true) === false) {
                return;
            }

            $index--;
        }

        $fixable = $phpcsFile->addFixableError(
            'in arrays, keys and "=>" must be on the same line',
            $stackPtr,
            'KeyAndValueNotOnSameLine',
        );

        if ($fixable !== true) {
            return;
        }

        $phpcsFile->fixer->beginChangeset();
        $phpcsFile->fixer->replaceToken($index, '');
        $phpcsFile->fixer->endChangeset();
    }

    /** @param list<int> $assignments */
    private function alignAssignments(File $phpcsFile, array $assignments, int $doubleArrowStartColumn): void
    {
        $tokens = $phpcsFile->getTokens();

        foreach ($assignments as $ptr) {
            $current = $tokens[$ptr];
            $column  = $current['column'];

            $beforeArrowPtr = $ptr - 1;
            $currentIndent  = strlen($tokens[$beforeArrowPtr]['content']);
            $correctIndent  = $currentIndent - $column + $doubleArrowStartColumn;

            if ($column === $doubleArrowStartColumn) {
                continue;
            }

            $fixable = $phpcsFile->addFixableError(
                sprintf(
                    'each "=>" assignment must be aligned; '
                    . 'current indentation before "=>" is %s space(s), must be %s space(s)',
                    $currentIndent,
                    $currentIndent,
                ),
                $ptr,
                'AssignmentsNotAligned',
            );

            if ($fixable === false) {
                continue;
            }

            $phpcsFile->fixer->beginChangeset();

            if ($tokens[$beforeArrowPtr]['code'] === T_WHITESPACE) {
                $phpcsFile->fixer->replaceToken($beforeArrowPtr, str_repeat(' ', $correctIndent));
                $phpcsFile->fixer->endChangeset();

                continue;
            }

            $phpcsFile->fixer->addContent($beforeArrowPtr, str_repeat(' ', $correctIndent));
            $phpcsFile->fixer->endChangeset();
        }
    }

    /** Find previous comma in array */
    private function getPreviousComma(File $phpcsFile, int $stackPtr, int $start): bool|int
    {
        $previousComma = false;
        $tokens        = $phpcsFile->getTokens();

        $ptr = $phpcsFile->findPrevious([T_COMMA, T_CLOSE_SHORT_ARRAY], $stackPtr, $start);

        while ($ptr !== false) {
            if ($tokens[$ptr]['code'] === T_COMMA) {
                $previousComma = $ptr;

                break;
            }

            if ($tokens[$ptr]['code'] === T_CLOSE_SHORT_ARRAY) {
                $ptr = $tokens[$ptr]['bracket_opener'];
            }

            $ptr = $phpcsFile->findPrevious([T_COMMA, T_CLOSE_SHORT_ARRAY], $ptr - 1, $start);
        }

        return $previousComma;
    }
}
