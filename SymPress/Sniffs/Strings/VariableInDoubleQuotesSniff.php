<?php

declare(strict_types=1);

namespace SymPressCS\SymPress\Sniffs\Strings;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

use function array_map;
use function array_slice;
use function count;
use function implode;
use function is_array;
use function ltrim;
use function preg_match;
use function sprintf;
use function strlen;
use function substr_replace;
use function token_get_all;

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
    public function process(File $phpcsFile, $stackPtr): int
    {
        $tokens = $phpcsFile->getTokens();

        // PHPCS splits multi-line strings into one token per line; PHP has to see the whole literal.
        $lastPtr = $stackPtr;
        $content = $tokens[$stackPtr]['content'];

        while (($tokens[$lastPtr + 1]['code'] ?? null) === T_DOUBLE_QUOTED_STRING) {
            $lastPtr++;
            $content .= $tokens[$lastPtr]['content'];
        }

        $replacements = [];

        foreach ($this->simpleInterpolations($content) as [$offset, $length, $expression, $braced]) {
            $tokenPtr = $stackPtr;

            while ($offset >= strlen($tokens[$tokenPtr]['content'])) {
                $offset -= strlen($tokens[$tokenPtr]['content']);
                $tokenPtr++;
            }

            $message = sprintf('must surround variable %s with { }', $expression);

            if ($braced === null) {
                $phpcsFile->addError($message, $tokenPtr, 'NotSurroundedWithBraces');

                continue;
            }

            if ($phpcsFile->addFixableError($message, $tokenPtr, 'NotSurroundedWithBraces') !== true) {
                continue;
            }

            $replacements[$tokenPtr][] = [$offset, $length, $braced];
        }

        $this->fix($phpcsFile, $replacements);

        return $lastPtr + 1;
    }

    /**
     * Finds variables interpolated without braces, using PHP's own lexer so escapes and
     * `$var->prop` / `$var[key]` forms are recognised exactly as PHP evaluates them.
     *
     * @return list<array{int, int, string, string|null}> Offset, length, original expression and
     *                                                    braced replacement (null if no safe fix).
     */
    private function simpleInterpolations(string $literal): array
    {
        $phpTokens = token_get_all("<?php {$literal};");
        $found     = [];
        $offset    = 0;
        $depth     = 0;
        $count     = count($phpTokens);

        // Index 0 is the open tag, which is not part of the literal.
        for ($i = 1; $i < $count; $i++) {
            $text = $this->text($phpTokens[$i]);

            if ($depth > 0 || $this->id($phpTokens[$i]) !== T_VARIABLE) {
                $depth += $this->depthChange($phpTokens[$i]);
                $offset += strlen($text);

                continue;
            }

            [$consumed, $braced] = $this->expression($phpTokens, $i);
            $expression = implode('', array_map($this->text(...), array_slice($phpTokens, $i, $consumed)));
            $found[] = [$offset, strlen($expression), $expression, $braced];
            $offset += strlen($expression);
            $i += $consumed - 1;
        }

        return $found;
    }

    /**
     * Returns how many tokens the simple interpolation starting at `$start` spans and its braced
     * equivalent, or null when no braced form is guaranteed to evaluate identically.
     *
     * @param array<int, array{0: int, 1: string, 2: int}|string> $phpTokens
     * @return array{int, string|null}
     */
    private function expression(array $phpTokens, int $start): array
    {
        $variable = $this->text($phpTokens[$start]);
        $next     = $phpTokens[$start + 1] ?? '';

        if (
            $this->id($next) === T_OBJECT_OPERATOR
            || $this->id($next) === T_NULLSAFE_OBJECT_OPERATOR
        ) {
            return [3, '{' . $variable . $this->text($next) . $this->text($phpTokens[$start + 2] ?? '') . '}'];
        }

        if ($next !== '[') {
            return [1, "{{$variable}}"];
        }

        // Collect the offset tokens up to the closing bracket.
        $key = [];

        for ($i = $start + 2; isset($phpTokens[$i]) && $phpTokens[$i] !== ']'; $i++) {
            $key[] = $phpTokens[$i];
        }

        // Unterminated offsets only occur in invalid code; report without fixing.
        if (!isset($phpTokens[$i])) {
            return [1, null];
        }

        $keyText = '';

        foreach ($key as $keyToken) {
            $keyText .= $this->text($keyToken);
        }

        return [$i - $start + 1, $this->bracedOffset($variable, $key, $keyText)];
    }

    /**
     * Unquoted keys are strings in simple syntax but constants in braced syntax, and keys like
     * `01` or `0x1` stay strings; only plain identifiers, decimal integers and variables are fixed.
     *
     * @param array<int, array{0: int, 1: string, 2: int}|string> $key
     */
    private function bracedOffset(string $variable, array $key, string $keyText): ?string
    {
        if (count($key) === 1 && $this->id($key[0]) === T_STRING) {
            return "{{$variable}['{$keyText}']}";
        }

        if (count($key) === 1 && $this->id($key[0]) === T_VARIABLE) {
            return "{{$variable}[{$keyText}]}";
        }

        // PHP's lexer reads only shorter decimals than PHP_INT_MAX as integers; "-0" stays a string.
        $isInteger = preg_match('/^(?:0|-?[1-9]\d*)$/', $keyText) === 1
            && strlen(ltrim($keyText, '-')) < strlen((string) PHP_INT_MAX);

        if ($isInteger) {
            return "{{$variable}[{$keyText}]}";
        }

        return null;
    }

    /** @param array{0: int, 1: string, 2: int}|string $token */
    private function depthChange(array|string $token): int
    {
        if ($token === '}') {
            return -1;
        }

        return $token === '{'
            || $this->id($token) === T_CURLY_OPEN
            || $this->id($token) === T_DOLLAR_OPEN_CURLY_BRACES ? 1 : 0;
    }

    /** @param array{0: int, 1: string, 2: int}|string $token */
    private function id(array|string $token): ?int
    {
        return is_array($token) ? $token[0] : null;
    }

    /** @param array{0: int, 1: string, 2: int}|string $token */
    private function text(array|string $token): string
    {
        return is_array($token) ? $token[1] : $token;
    }

    /** @param array<int, list<array{int, int, string}>> $replacements */
    private function fix(File $phpcsFile, array $replacements): void
    {
        if ($replacements === []) {
            return;
        }

        $tokens = $phpcsFile->getTokens();
        $phpcsFile->fixer->beginChangeset();

        foreach ($replacements as $tokenPtr => $tokenReplacements) {
            $content = $tokens[$tokenPtr]['content'];

            // Apply right to left so earlier offsets stay valid.
            for ($i = count($tokenReplacements) - 1; $i >= 0; $i--) {
                [$offset, $length, $braced] = $tokenReplacements[$i];
                $content = substr_replace($content, $braced, $offset, $length);
            }

            $phpcsFile->fixer->replaceToken($tokenPtr, $content);
        }

        $phpcsFile->fixer->endChangeset();
    }
}
