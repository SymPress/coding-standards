<?php

declare(strict_types=1);

namespace SymPressCS\SymPress\Sniffs\Usage;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;

use function is_int;

/**
 * Identify usage of is_null and replace it with a comparison against null.
 */
class IsNullSniff implements Sniff
{
    private const OPERAND_TOKENS = [
        T_VARIABLE                 => true,
        T_STRING                   => true,
        T_NS_SEPARATOR             => true,
        T_OBJECT_OPERATOR          => true,
        T_NULLSAFE_OBJECT_OPERATOR => true,
        T_DOUBLE_COLON             => true,
        T_SELF                     => true,
        T_STATIC                   => true,
        T_PARENT                   => true,
    ];

    private const SAFE_PREVIOUS_TOKENS = [
        T_OPEN_TAG            => true,
        T_OPEN_TAG_WITH_ECHO  => true,
        T_OPEN_PARENTHESIS    => true,
        T_OPEN_SQUARE_BRACKET => true,
        T_OPEN_SHORT_ARRAY    => true,
        T_OPEN_CURLY_BRACKET  => true,
        T_CLOSE_CURLY_BRACKET => true,
        T_SEMICOLON           => true,
        T_COMMA               => true,
        T_COLON               => true,
        T_RETURN              => true,
        T_ECHO                => true,
        T_BOOLEAN_AND         => true,
        T_BOOLEAN_OR          => true,
        T_LOGICAL_AND         => true,
        T_LOGICAL_OR          => true,
        T_LOGICAL_XOR         => true,
        T_INLINE_THEN         => true,
        T_INLINE_ELSE         => true,
        T_COALESCE            => true,
        T_DOUBLE_ARROW        => true,
        T_MATCH_ARROW         => true,
        T_FN_ARROW            => true,
    ];

    private const SAFE_NEXT_TOKENS = [
        T_CLOSE_TAG            => true,
        T_CLOSE_PARENTHESIS    => true,
        T_CLOSE_SQUARE_BRACKET => true,
        T_CLOSE_SHORT_ARRAY    => true,
        T_SEMICOLON            => true,
        T_COMMA                => true,
        T_COLON                => true,
        T_BOOLEAN_AND          => true,
        T_BOOLEAN_OR           => true,
        T_LOGICAL_AND          => true,
        T_LOGICAL_OR           => true,
        T_LOGICAL_XOR          => true,
        T_INLINE_THEN          => true,
        T_INLINE_ELSE          => true,
        T_COALESCE             => true,
        T_DOUBLE_ARROW         => true,
    ];

    /** @return array<int> */
    public function register(): array
    {
        return [T_STRING];
    }

    /**
     * @param int $stackPtr The position in the PHP_CodeSniffer file's token stack where the token
     * was found.
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint
     */
    public function process(File $phpcsFile, $stackPtr): void
    {
        $tokens = $phpcsFile->getTokens();

        if ($tokens[$stackPtr]['content'] !== 'is_null') {
            return;
        }

        $ignore = [
            T_DOUBLE_COLON             => true,
            T_OBJECT_OPERATOR          => true,
            T_NULLSAFE_OBJECT_OPERATOR => true,
            T_FUNCTION                 => true,
            T_CONST                    => true,
        ];

        // Check to make sure it's a function call to is_null (not $this->, etc.)
        $prevToken = $phpcsFile->findPrevious(T_WHITESPACE, $stackPtr - 1, null, true);

        if (isset($ignore[$tokens[$prevToken]['code']])) {
            return;
        }

        $nextToken = $phpcsFile->findNext(Tokens::$emptyTokens, $stackPtr + 1, null, true);

        if (
            $nextToken === false
            || $tokens[$nextToken]['code'] !== T_OPEN_PARENTHESIS
            || !isset($tokens[$nextToken]['parenthesis_closer'])
        ) {
            return;
        }

        $nsToken = null;

        if ($tokens[$prevToken]['code'] === T_NS_SEPARATOR) {
            $nsToken   = $prevToken;
            $prevToken = $phpcsFile->findPrevious(
                T_WHITESPACE,
                (int) $prevToken - 1,
                null,
                true,
            );

            if ($tokens[$prevToken]['code'] === T_STRING) {
                return;
            }
        }

        if (!$this->isSafeContext($phpcsFile, (int) $prevToken, $tokens[$nextToken]['parenthesis_closer'])) {
            $phpcsFile->addWarning(
                'Use a comparison against null instead of is_null',
                $stackPtr,
                'IsNull',
            );

            return;
        }

        $fix = $phpcsFile->addFixableWarning(
            'Use a comparison against null instead of is_null',
            $stackPtr,
            'IsNull',
        );

        if (!$fix) {
            return;
        }

        $stackPtrOpenParenthesis  = $nextToken;
        $stackPtrCloseParenthesis = $tokens[$nextToken]['parenthesis_closer'];

        $phpcsFile->fixer->beginChangeset();

        if (is_int($nsToken)) {
            $phpcsFile->fixer->replaceToken($nsToken, '');
        }

        $phpcsFile->fixer->replaceToken($stackPtr, '');

        $notNullComparison = $tokens[$prevToken]['code'] === T_BOOLEAN_NOT;
        $keepParentheses   = $this->keepParentheses(
            $phpcsFile,
            $stackPtrOpenParenthesis,
            $stackPtrCloseParenthesis,
        );

        if ($keepParentheses) {
            if ($notNullComparison) {
                // Remove the boolean not operator, it will be moved to the comparison operator.
                $phpcsFile->fixer->replaceToken((int) $prevToken, '');
                $replacement = ') !== null';

                $phpcsFile->fixer->replaceToken($stackPtrCloseParenthesis, $replacement);
                $phpcsFile->fixer->endChangeset();

                return;
            }

            $phpcsFile->fixer->replaceToken($stackPtrCloseParenthesis, ') === null');
            $phpcsFile->fixer->endChangeset();

            return;
        }

        // Remove opening parenthesis.
        $phpcsFile->fixer->replaceToken($stackPtrOpenParenthesis, '');

        // Remove following whitespace, if any.
        while ($tokens[$stackPtrOpenParenthesis + 1]['code'] === T_WHITESPACE) {
            $stackPtrOpenParenthesis++;
            $phpcsFile->fixer->replaceToken($stackPtrOpenParenthesis, '');
        }

        $replacement = ' === null';

        if ($notNullComparison) {
            // Remove the boolean not operator, it will be moved to the comparison operator.
            $phpcsFile->fixer->replaceToken((int) $prevToken, '');
            $replacement = ' !== null';
        }

        $ptrBeforeCloseParenthesis = $stackPtrCloseParenthesis;

        // Remove whitespace preceding closing parenthesis, if any.
        while ($tokens[$ptrBeforeCloseParenthesis - 1]['code'] === T_WHITESPACE) {
            $ptrBeforeCloseParenthesis--;
            $phpcsFile->fixer->replaceToken($ptrBeforeCloseParenthesis, '');
        }

        $phpcsFile->fixer->replaceToken($stackPtrCloseParenthesis, $replacement);

        $phpcsFile->fixer->endChangeset();
    }

    /**
     * The parentheses can only be dropped when the argument is a single operand, e.g.
     * `$var`, `$obj->prop`, `$arr['key']` or `Foo::bar($x)`; any operator could bind
     * differently once `=== null` follows.
     */
    private function keepParentheses(
        File $phpcsFile,
        int $stackPtrOpenParenthesis,
        int $stackPtrCloseParenthesis,
    ): bool {

        $tokens = $phpcsFile->getTokens();

        for ($ptr = $stackPtrOpenParenthesis + 1; $ptr < $stackPtrCloseParenthesis; $ptr++) {
            $code = $tokens[$ptr]['code'];

            // Call arguments and offsets are grouped already.
            if ($code === T_OPEN_PARENTHESIS || $code === T_OPEN_SQUARE_BRACKET) {
                $ptr = $tokens[$ptr]['parenthesis_closer'] ?? $tokens[$ptr]['bracket_closer'];

                continue;
            }

            if (!isset(self::OPERAND_TOKENS[$code]) && !isset(Tokens::$emptyTokens[$code])) {
                return true;
            }
        }

        return false;
    }

    /**
     * `X === null` replaces the call result only if the neighbouring operators bind looser than
     * `===`, e.g. `is_null($a) . 'x'` or `(int) is_null($a)` would change meaning.
     * This also covers comparisons with the result, like `$a === is_null($b)`.
     */
    private function isSafeContext(File $phpcsFile, int $prevPtr, int $closePtr): bool
    {
        $tokens = $phpcsFile->getTokens();

        if ($tokens[$prevPtr]['code'] === T_BOOLEAN_NOT) {
            $prevPtr = (int) $phpcsFile->findPrevious(Tokens::$emptyTokens, $prevPtr - 1, null, true);
        }

        $prevCode = $tokens[$prevPtr]['code'];

        if (!isset(self::SAFE_PREVIOUS_TOKENS[$prevCode]) && !isset(Tokens::$assignmentTokens[$prevCode])) {
            return false;
        }

        $nextPtr = $phpcsFile->findNext(Tokens::$emptyTokens, $closePtr + 1, null, true);

        return $nextPtr !== false && isset(self::SAFE_NEXT_TOKENS[$tokens[$nextPtr]['code']]);
    }
}
