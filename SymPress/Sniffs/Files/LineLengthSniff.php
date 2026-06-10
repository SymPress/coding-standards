<?php

declare(strict_types=1);

namespace SymPressCS\SymPress\Sniffs\Files;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;

final class LineLengthSniff implements Sniff
{
    /**
     * The limit that the length of a line should not exceed.
     */
    public int $lineLimit = 100;

    /** @var list<string> */
    public array $allowedLongStringFunctions = [];

    /** @return list<int|string> */
    public function register(): array
    {
        return [
            T_OPEN_TAG,
        ];
    }

    /**
     * @param File $phpcsFile
     * @param int $stackPtr
     */
    public function process(File $phpcsFile, $stackPtr): int
    {
        $longLinesData = $this->collectLongLinesData($phpcsFile, max(1, $stackPtr));

        foreach ($longLinesData as [$length, $stackPtr]) {
            $phpcsFile->addWarning(
                'Line length (%d) exceeds allowed maximum of %d characters',
                $stackPtr,
                'TooLong',
                [$length, $this->lineLimit],
            );
        }

        // Ignore the rest of the file.
        return $phpcsFile->numTokens + 1;
    }

    /**
     * @param File $file
     * @param int $start
     * @return list<array{int, int}>
     */
    private function collectLongLinesData(File $file, int $start): array
    {
        /** @var array<int, array<string, mixed>> $tokens */
        $tokens = $file->getTokens();

        /** @var array<int, array{length:int, nonEmptyLength:int, start:int, end:int|null}> $data */
        $data = [];

        $lastLine = 0;

        for ($i = $start; $i < $file->numTokens; $i++) {
            // Still processing previous line: increment length and continue.
            if (($lastLine > 0) && ($tokens[$i]['line'] === $lastLine)) {
                $content                            = (string) $tokens[$i]['content'];
                $data[$lastLine]['length']         += strlen($content);
                $data[$lastLine]['nonEmptyLength'] += strlen(trim($content));

                continue;
            }

            // A new line started: let's set "end" for the previous line.
            if (($lastLine > 0) && isset($data[$lastLine])) {
                $data[$lastLine]['end'] = $i - 1;
            }

            $lastLine        = (int) $tokens[$i]['line'];
            $content         = (string) $tokens[$i]['content'];
            $data[$lastLine] = [
                'length'         => strlen($content),
                'nonEmptyLength' => strlen(trim($content)),
                'start'          => $i,
                'end'            => null,
            ];
        }

        // We still have to set the "end" for the last line of the file
        if (($lastLine > 0) && ($data[$lastLine]['end'] === null)) {
            $data[$lastLine]['end'] = $i - 1;
        }

        $longLines = [];

        /** @var array{length:int, nonEmptyLength:int, start:int, end:int|null} $lineData */
        foreach ($data as $lineData) {
            if ($this->isLengthAcceptable($lineData, $file, $tokens)) {
                continue;
            }

            $longLines[] = [$lineData['length'], $lineData['start']];
        }

        return $longLines;
    }

    /**
     * @param array{length:int, nonEmptyLength:int, start:int, end:int|null} $lineData
     * @param File $file
     * @param array<int, array<string, mixed>> $tokens
     */
    private function isLengthAcceptable(array $lineData, File $file, array $tokens): bool
    {
        // Ignore empty lines.
        if ($lineData['nonEmptyLength'] === 0) {
            return true;
        }

        // 1 char of tolerance.
        if ($lineData['length'] - $this->lineLimit <= 1) {
            return true;
        }

        $lineEnd = $lineData['end'] ?? $lineData['start'];

        return $this->isLongUse($file, $tokens, $lineData['start'], $lineEnd)
            || $this->isLongAllowedFunctionString($file, $tokens, $lineData['start'], $lineEnd)
            || $this->isLongWord($file, $tokens, $lineData['start'], $lineEnd);
    }

    /**
     * With deep namespace structure and long namespaces/class names it might happen that a `use`
     * statement becomes longer than the limit.
     * We do not trigger a warning in that case because it is not possible to split the line.
     *
     * @param File $file
     * @param array<int, array<string, mixed>> $tokens
     * @param int $start
     * @param int $end
     */
    private function isLongUse(File $file, array $tokens, int $start, int $end): bool
    {
        $usePos = $file->findNext(T_USE, $start, $end);

        if ($usePos === false) {
            return false;
        }

        $useLen = 0;

        for ($i = $usePos; $i <= $file->findEndOfStatement($usePos); $i++) {
            $useLen += strlen((string) $tokens[$i]['content']);
        }

        return $useLen > $this->lineLimit;
    }

    /**
     * Some coding standards require literal strings for specific function arguments.
     * When line length is caused by such a string, splitting it would only create another
     * coding-standard violation, so configured function calls are ignored.
     *
     * @param File $file
     * @param array<int, array<string, mixed>> $tokens
     * @param int $start
     * @param int $end
     */
    private function isLongAllowedFunctionString(File $file, array $tokens, int $start, int $end): bool
    {
        if (!$this->allowedLongStringFunctions) {
            return false;
        }

        $stringPos = $file->findNext(
            [T_CONSTANT_ENCAPSED_STRING, T_DOUBLE_QUOTED_STRING],
            $start,
            $end,
        );

        if ($stringPos === false) {
            return false;
        }

        $open = $file->findPrevious(T_OPEN_PARENTHESIS, $stringPos, null, false, null, true);

        if ($open === false) {
            return false;
        }

        $functionPos = $file->findPrevious(T_STRING, $open, null, false, null, true);

        if ($functionPos === false) {
            return false;
        }

        $function = strtolower((string) $tokens[$functionPos]['content']);

        if (!in_array($function, $this->normalizedAllowedLongStringFunctions(), true)) {
            return false;
        }

        $targetLine = $tokens[$stringPos]['line'];

        $textLen = 0;

        for ($i = $open + 1; $i < $file->findEndOfStatement($open) - 1; $i++) {
            if ($tokens[$i]['line'] !== $targetLine) {
                continue;
            }

            $textLen += max(1, strlen((string) $tokens[$i]['content']));
        }

        return $textLen + 2 > $this->lineLimit;
    }

    /** @return list<string> */
    private function normalizedAllowedLongStringFunctions(): array
    {
        $functions = [];

        foreach ($this->allowedLongStringFunctions as $function) {
            if (!is_string($function)) {
                continue;
            }

            $function = strtolower(trim($function));
            if ($function === '') {
                continue;
            }

            $functions[] = $function;
        }

        return array_values(array_unique($functions));
    }

    /**
     * We don't want to split a single word into multiple lines.
     * If there is a single word like a URL that is longer than the maximum line length, we don't
     * show warnings for it.
     *
     * @param File $file
     * @param array<int, array<string, mixed>> $tokens
     * @param int $start
     * @param int $end
     */
    private function isLongWord(File $file, array $tokens, int $start, int $end): bool
    {
        $targetTypes = Tokens::$textStringTokens + [T_DOC_COMMENT_STRING => T_DOC_COMMENT_STRING];

        $foundString = false;

        while ($start && ($start <= $end)) {
            if (!in_array($tokens[$start]['code'], $targetTypes, true)) {
                $start++;

                continue;
            }

            if ($foundString) {
                return false;
            }

            $foundString = true;

            $isHtml = $tokens[$start]['code'] === T_INLINE_HTML;

            $isLong = $isHtml
                ? $this->isLongHtmlAttribute($start, $end, $file, $tokens)
                : $this->isLongSingleWord($start, $end, $file, $tokens);

            if (!$isLong) {
                return false;
            }

            if ($isHtml) {
                return true;
            }

            $start++;
        }

        return true;
    }

    /**
     * @param int $position
     * @param int $lineEnd
     * @param File $file
     * @param array<int, array<string, mixed>> $tokens
     */
    private function isLongHtmlAttribute(
        int $position,
        int $lineEnd,
        File $file,
        array $tokens,
    ): bool {

        $inPhp = false;

        $content = '';

        for ($i = $position; $i <= $lineEnd; $i++) {
            $code = $tokens[$i]['code'];

            $inPhp = $inPhp || $code === T_OPEN_TAG || $code === T_OPEN_TAG_WITH_ECHO;

            if ($inPhp || $code === T_INLINE_HTML) {
                $tokenContent = (string) $tokens[$i]['content'];
                $content     .= $inPhp ? str_repeat('x', strlen($tokenContent)) : $tokenContent;
            }

            $inPhp = $code !== T_CLOSE_TAG;
        }

        // Instead of counting single _word_ length, we will count single _attribute_ length.
        preg_match_all('~\S+\s*=\s*["\'][^"\']*["\']~', $content, $matches);

        $attributesNumber = count($matches[0]);

        // When multiple HTML attributes are there, each attribute can go in a separate line.
        if ($attributesNumber > 1) {
            return false;
        }

        // When a single HTML attribute is too long, we are not going to trigger warnings,
        // since we don't want to split one attribute into multiple lines.
        if ($attributesNumber === 1) {
            return true;
        }

        // No HTML attributes found, let's use standard approach.
        return $this->isLongSingleWord($position, $lineEnd, $file, $tokens);
    }

    /**
     * @param int $position
     * @param int $lineEnd
     * @param File $file
     * @param array<int, array<string, mixed>> $tokens
     */
    private function isLongSingleWord(
        int $position,
        int $lineEnd,
        File $file,
        array $tokens,
    ): bool {

        $words = preg_split(
            '~\s+~',
            (string) $tokens[$position]['content'],
            2,
            PREG_SPLIT_NO_EMPTY,
        );

        // If multiple words exceed line limit, we can split each word into its own line.
        if ($words === false || count($words) !== 1) {
            return false;
        }

        $firstNonWhitePos = $file->findNext(T_WHITESPACE, $position, $lineEnd, true);

        $firstNonWhite = $firstNonWhitePos === false ? null : $tokens[$firstNonWhitePos];

        return strlen((string) reset($words))
            + (is_array($firstNonWhite) ? (int) ($firstNonWhite['column'] ?? 1) + 3 : 4)
            > $this->lineLimit;
    }
}
