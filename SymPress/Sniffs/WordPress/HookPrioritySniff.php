<?php

declare(strict_types=1);

namespace SymPressCS\SymPress\Sniffs\WordPress;

use PHPCSUtils\Utils\PassedParameters;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

final class HookPrioritySniff implements Sniff
{
    /** @return list<int|string> */
    public function register(): array
    {
        return [
            T_STRING,
        ];
    }

    public function process(File $phpcsFile, $stackPtr): void
    {
        $tokens = $phpcsFile->getTokens();

        $functionName = $tokens[$stackPtr]['content'] ?? '';

        if ($functionName !== 'add_filter' && $functionName !== 'add_action') {
            return;
        }

        $parameter = PassedParameters::getParameter($phpcsFile, $stackPtr, 3, 'priority');
        $parameter = $parameter['clean'] ?? '';

        if ($parameter === 'PHP_INT_MIN') {
            $phpcsFile->addWarning(
                'Found "%s" used as hook priority; '
                . 'this makes it hard, if not impossible to reliably remove the callback',
                $stackPtr,
                'PHP_INT_MIN',
                [$parameter],
            );

            return;
        }

        if ($parameter !== 'PHP_INT_MAX' || $functionName !== 'add_filter') {
            return;
        }

        $phpcsFile->addWarning(
            'Found "%s" used as hook priority; '
            . 'this makes it hard, if not impossible to reliably filter the callback output',
            $stackPtr,
            'PHP_INT_MAX',
            [$parameter],
        );
    }
}
