<?php

declare(strict_types=1);

namespace SymPressCS\SymPress\Sniffs\Strings;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

use function preg_match_all;
use function sprintf;
use function str_contains;
use function strlen;
use function strpos;
use function strrpos;
use function substr;

/**
 * Variable in Double Quotes sniff.
 *
 * Variables in double quotes must be surrounded by { }
 */
class VariableInDoubleQuotesSniff implements Sniff
{
    /**
     * Registers the tokens that this sniff wants to listen for.
     *
     * @see Tokens.php
     * @return array<int, string>
     */
    public function register(): array
    {
        return [T_DOUBLE_QUOTED_STRING];
    }

    /**
     * Called when one of the token types that this sniff is listening for
     * is found.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint
     */
    public function process(File $phpcsFile, $stackPtr): void
    {
        $varRegExp = '/\$[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*/';

        $tokens  = $phpcsFile->getTokens();
        $content = $tokens[$stackPtr]['content'];

        $matches = [];

        preg_match_all($varRegExp, $content, $matches, PREG_OFFSET_CAPTURE);

        foreach ($matches[0] as [$var, $pos]) {
            $this->processVariableMatch($phpcsFile, $stackPtr, $content, $var, $pos);
        }
    }

    private function processVariableMatch(
        File $phpcsFile,
        int $stackPtr,
        string $content,
        string $var,
        int $pos,
    ): void {

        if ($this->isAlreadySurrounded($content, $pos)) {
            return;
        }

        $fix = $phpcsFile->addFixableError(
            sprintf(
                'must surround variable %s with { }',
                $var,
            ),
            $stackPtr,
            'NotSurroundedWithBraces',
        );

        if ($fix !== true) {
            return;
        }

        $correctVariable = $this->surroundVariableWithBraces(
            $content,
            $pos,
            $var,
        );

        $this->fixPhpCsFile($stackPtr, $correctVariable, $phpcsFile);
    }

    private function isAlreadySurrounded(string $content, int $pos): bool
    {
        if ($pos !== 1 && $content[$pos - 1] === '{') {
            return true;
        }

        $beforeVariable = substr($content, 0, $pos);

        if (strpos($beforeVariable, '{') > 0 && !str_contains($beforeVariable, '}')) {
            return true;
        }

        $lastOpeningBrace = strrpos($beforeVariable, '{');

        if ($lastOpeningBrace === false || $content[$lastOpeningBrace + 1] !== '$') {
            return false;
        }

        $lastClosingBrace = strrpos($beforeVariable, '}');

        return $lastClosingBrace !== false && $lastClosingBrace < $lastOpeningBrace;
    }

    /** Surrounds a variable with curly brackets */
    private function surroundVariableWithBraces(string $content, int $pos, string $var): string
    {
        return substr($content, 0, $pos)
            . '{'
            . $var
            . '}'
            . substr($content, $pos + strlen($var));
    }

    /** Fixes the file */
    private function fixPhpCsFile(int $stackPtr, string $correctVariable, File $phpCsFile): void
    {
        $phpCsFile->fixer->beginChangeset();
        $phpCsFile->fixer->replaceToken($stackPtr, $correctVariable);
        $phpCsFile->fixer->endChangeset();
    }
}
