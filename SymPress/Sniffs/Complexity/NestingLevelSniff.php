<?php

declare(strict_types=1);

namespace SymPressCS\SymPress\Sniffs\Complexity;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

use function filter_var;

class NestingLevelSniff implements Sniff
{
    public int $warningLimit            = 3;
    public int $errorLimit              = 5;
    public bool $ignoreTopLevelTryBlock = true;

    /** @return list<int> */
    public function register(): array
    {
        return [T_FUNCTION];
    }

    /**
     * @param int $stackPtr
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint
     */
    public function process(File $phpcsFile, $stackPtr): void
    {
        /** @var array<int, array<string, mixed>> $tokens */
        $tokens = $phpcsFile->getTokens();

        // Ignore abstract methods.
        if (isset($tokens[$stackPtr]['scope_opener']) === false) {
            return;
        }

        $start = (int) $tokens[$stackPtr]['scope_opener'];
        $end   = (int) $tokens[$stackPtr]['scope_closer'];

        $baseLevel        = (int) $tokens[$stackPtr]['level'];
        $nestingLevel     = 0;
        $insideIgnoredTry = false;
        $ignoredTryEnd    = null;
        $tryTargetLevel   = filter_var($this->ignoreTopLevelTryBlock, FILTER_VALIDATE_BOOLEAN)
            ? $baseLevel + 1
            : $baseLevel - 2; // This is an impossible level, so the conditions below will be false

        // Find the maximum nesting level of any token in the function.
        for ($i = $start + 1; $i < $end; $i++) {
            if ($insideIgnoredTry && $ignoredTryEnd !== null && $i === $ignoredTryEnd) {
                $insideIgnoredTry = false;
                $ignoredTryEnd = null;

                continue;
            }

            $level = (int) $tokens[$i]['level'];

            if (!$insideIgnoredTry && $tokens[$i]['code'] === T_TRY && $level === $tryTargetLevel) {
                $insideIgnoredTry = true;

                continue;
            }

            if (
                $insideIgnoredTry
                && ($ignoredTryEnd === null)
                && ($tokens[$i]['code'] === T_CATCH || $tokens[$i]['code'] === T_FINALLY)
                && $level === $tryTargetLevel
            ) {
                $ignoredTryEnd = $this->endOfTryBlock($i, $phpcsFile);

                continue;
            }

            $level -= (int) $insideIgnoredTry;

            if ($level <= $nestingLevel) {
                continue;
            }

            $nestingLevel = $level;
        }

        // We subtract the nesting level of the function itself .
        $nestingLevel -= $baseLevel + 1;

        $this->validate($nestingLevel, $phpcsFile, $stackPtr);
    }

    private function validate(int $nestingLevel, File $phpcsFile, int $stackPtr): void
    {
        $isError   = $nestingLevel >= $this->errorLimit;
        $isWarning = !$isError && ($nestingLevel >= $this->warningLimit);

        if (!$isError && !$isWarning) {
            return;
        }

        $message  = 'Function\'s nesting level (%s) exceeds %s';
        $message .= $isError ? ', please refactor it.' : ', consider to refactor it.';

        $code  = $isError ? 'MaxExceeded' : 'High';
        $limit = $isError ? $this->errorLimit : $this->warningLimit;

        $isError
            ? $phpcsFile->addError($message, $stackPtr, $code, [$nestingLevel, $limit])
            : $phpcsFile->addWarning($message, $stackPtr, $code, [$nestingLevel, $limit]);
    }

    private function endOfTryBlock(int $catchPosition, File $phpcsFile): int
    {
        /** @var array<int, array<string, mixed>> $tokens */
        $tokens     = $phpcsFile->getTokens();
        $currentEnd = (int) $tokens[$catchPosition]['scope_closer'];
        $nextCatch  = $phpcsFile->findNext(T_CATCH, $currentEnd + 1, $currentEnd + 3);

        if ($nextCatch !== false) {
            return $this->endOfTryBlock($nextCatch, $phpcsFile);
        }

        $finally = $phpcsFile->findNext(T_FINALLY, $currentEnd + 1, $currentEnd + 3);

        return $finally !== false
            ? (int) $tokens[$finally]['scope_closer'] + 1
            : $currentEnd + 1;
    }
}
