<?php

declare(strict_types=1);

namespace SymPressCS\SymPress\Sniffs\WordPress;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use SymPressCS\SymPress\Helpers\Boundaries;
use SymPressCS\SymPress\Helpers\FunctionReturnStatement;
use SymPressCS\SymPress\Helpers\Hooks;

final class HookClosureReturnSniff implements Sniff
{
    /** @return list<int|string> */
    public function register(): array
    {
        return [
            T_CLOSURE,
            T_FN,
        ];
    }

    public function process(File $phpcsFile, $stackPtr): void
    {
        if (!Hooks::isHookClosure($phpcsFile, $stackPtr)) {
            return;
        }

        [$functionStart, $functionEnd] = Boundaries::functionBoundaries($phpcsFile, $stackPtr);

        if ($functionStart < 0 || $functionEnd <= 0) {
            return;
        }

        $returnData = FunctionReturnStatement::allInfo($phpcsFile, $stackPtr);

        $voidReturnCount = $returnData['void'];

        // Allow a filter to return null on purpose.
        $nonVoidReturnCount = $returnData['nonEmpty'] + $returnData['null'];

        if (Hooks::isHookClosure($phpcsFile, $stackPtr, true, false)) {
            if ($voidReturnCount || !$nonVoidReturnCount) {
                $phpcsFile->addError(
                    'No (or void) return found for filter closure',
                    $stackPtr,
                    'NoReturnFromFilter',
                );
            }

            return;
        }

        if (!$nonVoidReturnCount) {
            return;
        }

        $phpcsFile->addError(
            'Return value found for action closure',
            $stackPtr,
            'ReturnFromAction',
        );
    }
}
