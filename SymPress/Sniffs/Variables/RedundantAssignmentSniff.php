<?php

declare(strict_types=1);

namespace SymPressCS\SymPress\Sniffs\Variables;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;

final class RedundantAssignmentSniff implements Sniff
{
    /** @return list<int|string> */
    public function register(): array
    {
        return [
            T_ECHO,
            T_PRINT,
            T_RETURN,
            T_THROW,
            T_YIELD,
            T_YIELD_FROM,
        ];
    }

    /**
     * @param int $stackPtr
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint
     */
    public function process(File $phpcsFile, $stackPtr): void
    {
        $statementEnd = $phpcsFile->findNext(T_SEMICOLON, $stackPtr + 1);
        if (!is_int($statementEnd)) {
            return;
        }

        if ($this->containsNestedFunction($phpcsFile, $stackPtr, $statementEnd)) {
            return;
        }

        $usedVariables = $this->singleUseVariables($phpcsFile, $stackPtr, $statementEnd);
        if (!$usedVariables) {
            return;
        }

        foreach ($usedVariables as $usedVariable) {
            $assignment = $this->assignmentForUsage($phpcsFile, $stackPtr, $usedVariable);
            if ($assignment === null) {
                continue;
            }

            $phpcsFile->addWarning(
                'Variable "%s" is assigned only to be used by the following %s statement; inline the value.',
                $stackPtr,
                'Found',
                [$usedVariable, $this->statementName($phpcsFile, $stackPtr)],
            );
        }
    }

    /** @return list<string> */
    private function singleUseVariables(File $file, int $statementStart, int $statementEnd): array
    {
        $tokens = $file->getTokens();
        $variables = [];

        for ($i = $statementStart + 1; $i < $statementEnd; $i++) {
            if ($tokens[$i]['code'] !== T_VARIABLE) {
                continue;
            }

            $variable = $tokens[$i]['content'];
            $variables[$variable] = ($variables[$variable] ?? 0) + 1;
        }

        return array_keys(array_filter($variables, static fn (int $count): bool => $count === 1));
    }

    private function containsNestedFunction(File $file, int $statementStart, int $statementEnd): bool
    {
        $tokens = $file->getTokens();

        for ($i = $statementStart + 1; $i < $statementEnd; $i++) {
            if (in_array($tokens[$i]['code'], [T_CLOSURE, T_FN], true)) {
                return true;
            }
        }

        return false;
    }

    /** @return array{start: int, operator: int, end: int}|null */
    private function assignmentForUsage(
        File $file,
        int $statementStart,
        string $variable,
    ): ?array {

        $tokens = $file->getTokens();

        $previous = $file->findPrevious(Tokens::$emptyTokens, $statementStart - 1, null, true);
        if (!is_int($previous) || ($tokens[$previous]['code'] !== T_SEMICOLON)) {
            return null;
        }

        $immediateAssignment = $this->assignmentEndingAt($file, $previous, $variable);
        if ($immediateAssignment !== null) {
            return $immediateAssignment;
        }

        $scopeStart = $this->functionScopeStart($tokens, $statementStart);
        $conditions = $tokens[$statementStart]['conditions'] ?? [];

        for ($i = $previous - 1; $i > $scopeStart; $i--) {
            if (($tokens[$i]['code'] !== T_SEMICOLON) || (($tokens[$i]['conditions'] ?? []) !== $conditions)) {
                continue;
            }

            $assignment = $this->assignmentEndingAt($file, $i, $variable);
            if ($assignment === null) {
                continue;
            }

            if (!$this->variableIsUsedBetween($file, $variable, $assignment['end'] + 1, $statementStart)) {
                return $assignment;
            }
        }

        return null;
    }

    /** @return array{start: int, operator: int, end: int}|null */
    private function assignmentEndingAt(File $file, int $assignmentEnd, string $variable): ?array
    {
        $tokens = $file->getTokens();

        $boundary = $file->findPrevious(
            [T_OPEN_CURLY_BRACKET, T_CLOSE_CURLY_BRACKET, T_SEMICOLON],
            $assignmentEnd - 1,
        );

        $statementStart = is_int($boundary) ? $boundary + 1 : 0;

        $assignedVariable = $file->findNext(Tokens::$emptyTokens, $statementStart, $assignmentEnd, true);
        if (!is_int($assignedVariable) || ($tokens[$assignedVariable]['code'] !== T_VARIABLE)) {
            return null;
        }

        if ($tokens[$assignedVariable]['content'] !== $variable) {
            return null;
        }

        $assignmentOperator = $file->findNext(Tokens::$emptyTokens, $assignedVariable + 1, $assignmentEnd, true);
        if (!is_int($assignmentOperator) || ($tokens[$assignmentOperator]['code'] !== T_EQUAL)) {
            return null;
        }

        if ($this->variableIsUsedBetween($file, $variable, $assignmentOperator + 1, $assignmentEnd)) {
            return null;
        }

        return [
            'start'    => $assignedVariable,
            'operator' => $assignmentOperator,
            'end'      => $assignmentEnd,
        ];
    }

    /** @param array<int, array<string, mixed>> $tokens */
    private function functionScopeStart(array $tokens, int $position): int
    {
        for ($i = $position; $i >= 0; $i--) {
            if (
                $tokens[$i]['code'] === T_FUNCTION
                && isset($tokens[$i]['scope_opener'], $tokens[$i]['scope_closer'])
                && $tokens[$i]['scope_opener'] < $position
                && $tokens[$i]['scope_closer'] > $position
            ) {
                return (int) $tokens[$i]['scope_opener'];
            }
        }

        return 0;
    }

    private function variableIsUsedBetween(File $file, string $variable, int $start, int $end): bool
    {
        $tokens = $file->getTokens();

        for ($i = $start; $i < $end; $i++) {
            if (($tokens[$i]['code'] === T_VARIABLE) && ($tokens[$i]['content'] === $variable)) {
                return true;
            }
        }

        return false;
    }

    private function statementName(File $file, int $position): string
    {
        return match ($file->getTokens()[$position]['code']) {
            T_ECHO => 'echo',
            T_PRINT => 'print',
            T_RETURN => 'return',
            T_THROW => 'throw',
            T_YIELD, T_YIELD_FROM => 'yield',
            default => 'following',
        };
    }
}
