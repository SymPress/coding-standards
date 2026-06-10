<?php

declare(strict_types=1);

namespace SymPressCS\SymPress\Sniffs\Encoding;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

use function is_string;
use function str_contains;

final class Utf8EncodingCommentSniff implements Sniff
{
    private const COMMENT = '-*- coding: utf-8 -*-';

    /** @return list<int|string> */
    public function register(): array
    {
        return [
            T_COMMENT,
        ];
    }

    public function process(File $phpcsFile, $stackPtr): void
    {
        $tokens = $phpcsFile->getTokens();

        $comment = $tokens[$stackPtr]['content'] ?? null;

        if (!is_string($comment)) {
            return;
        }

        if (!str_contains($comment, self::COMMENT)) {
            return;
        }

        $message = 'Outdated UTF-8 encoding declaration comment found';

        if (!$phpcsFile->addFixableWarning($message, $stackPtr, 'Found')) {
            return;
        }

        $this->fix($phpcsFile, $stackPtr);
    }

    private function fix(File $file, int $position): void
    {
        $fixer = $file->fixer;

        $fixer->beginChangeset();

        $fixer->replaceToken($position, '');

        $fixer->endChangeset();
    }
}
