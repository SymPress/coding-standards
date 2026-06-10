<?php

declare(strict_types=1);

namespace SymPressCS\SymPress\Sniffs\Classes;

use PHPCSUtils\Tokens\Collections;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use SymPressCS\SymPress\Helpers\Names;
use SymPressCS\SymPress\Helpers\Objects;

use function array_keys;

final class PropertyLimitSniff implements Sniff
{
    public int $maxCount = 10;

    /** @return list<int|string> */
    public function register(): array
    {
        return array_keys(Collections::ooPropertyScopes());
    }

    public function process(File $phpcsFile, $stackPtr): void
    {
        $count = Objects::countProperties($phpcsFile, $stackPtr);

        if ($count <= $this->maxCount) {
            return;
        }

        $tokenTypeName = Names::tokenTypeName($phpcsFile, $stackPtr);

        $phpcsFile->addWarning(
            'Number of %s properties (%d) exceeds allowed maximum of %d',
            $stackPtr,
            'TooManyProperties',
            [$tokenTypeName, $count, $this->maxCount],
        );
    }
}
