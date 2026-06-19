<?php

declare(strict_types=1);

namespace SymPressCS\SymPress\Sniffs\Files;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

final class FileLengthSniff implements Sniff
{
    public int $maxLength = 1000;

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
        $length = $this->fileLength($phpcsFile);
        if ($length > $this->maxLength) {
            $phpcsFile->addWarning(
                'File length (%d) exceeds allowed maximum of %d lines',
                $stackPtr,
                'TooLong',
                [$length, $this->maxLength],
            );
        }

        return $phpcsFile->numTokens + 1;
    }

    private function fileLength(File $file): int
    {
        $contents = file_get_contents($file->getFilename());

        if (!is_string($contents) || $contents === '') {
            return 0;
        }

        return substr_count($contents, "\n") + (str_ends_with($contents, "\n") ? 0 : 1);
    }
}
