<?php

declare(strict_types=1);

namespace SymPressCS\SymPress\Sniffs\Formatting;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Standards\PSR2\Sniffs\Namespaces\UseDeclarationSniff;
use PHP_CodeSniffer\Util\Common;
use PHP_CodeSniffer\Util\Tokens as PHP_CodeSniffer_Tokens;

use function implode;
use function in_array;
use function min;
use function sprintf;
use function strcasecmp;
use function strcmp;
use function strcoll;
use function strlen;
use function substr;

/**
 * Alphabetical Use Statements sniff.
 *
 * Use statements must be in alphabetical order, grouped by empty lines.
 */
class AlphabeticalUseStatementsSniff extends UseDeclarationSniff
{
    private const NAMESPACE_SEPARATOR_STRING = '\\';

    private const SUPPORTED_ORDERING_METHODS = [
        'dictionary',
        'string',
        'string',
        'string-locale',
        'string-case-insensitive',
    ];

    /**
     * Sorting order, see SUPPORTED_ORDERING_METHODS for possible settings
     *
     * Unknown types will be mapped to 'string'.
     */
    public string $order = 'dictionary';

    /** Last import seen in group */
    private string $lastImport = '';

    /** Line number of the last seen use statement */
    private int $lastLine = -1;

    private string $currentFile = '';

    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param int  $stackPtr  The position of the current token in
     *                        the stack passed in $tokens.
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint
     */
    public function process(File $phpcsFile, $stackPtr): void
    {
        if (in_array($this->order, self::SUPPORTED_ORDERING_METHODS, true) === false) {
            $error = sprintf(
                "'%s' is not a valid order function for %s! Pick one of: %s",
                $this->order,
                Common::getSniffCode(self::class),
                implode(', ', self::SUPPORTED_ORDERING_METHODS),
            );

            $phpcsFile->addError($error, $stackPtr, 'InvalidOrder');

            return;
        }

        parent::process($phpcsFile, $stackPtr);

        if ($this->currentFile !== $phpcsFile->getFilename()) {
            $this->lastLine    = -1;
            $this->lastImport  = '';
            $this->currentFile = $phpcsFile->getFilename();
        }

        $tokens = $phpcsFile->getTokens();
        $line   = $tokens[$stackPtr]['line'];

        // Ignore closure imports.
        $isNonImportUse = $this->checkIsNonImportUse($phpcsFile, $stackPtr);

        if ($isNonImportUse === true) {
            return;
        }

        $currentImportArr = $this->getUseImport($phpcsFile, $stackPtr);

        if ($currentImportArr === false) {
            return;
        }

        $currentPtr    = $currentImportArr['startPtr'];
        $currentImport = $currentImportArr['content'];

        if ($this->lastLine + 1 < $line) {
            $this->lastLine   = $line;
            $this->lastImport = $currentImport;

            return;
        }

        $fixable = false;

        if (
            $this->lastImport !== ''
            && $this->compareString($this->lastImport, $currentImport) > 0
        ) {
            $msg     = 'USE statements must be sorted alphabetically, order %s';
            $code    = 'MustBeSortedAlphabetically';
            $fixable = $phpcsFile->addFixableError($msg, $currentPtr, $code, [$this->order]);
        }

        if ($fixable === true) {
            // Find the correct position in current use block.
            $newDestinationPtr
                = $this->findNewDestination($phpcsFile, $stackPtr, $currentImport);

            $currentUseStr = $this->getUseStatementAsString($phpcsFile, $stackPtr);

            $phpcsFile->fixer->beginChangeset();
            $phpcsFile->fixer->addContentBefore($newDestinationPtr, $currentUseStr);
            $this->fixerClearLine($phpcsFile, $stackPtr);
            $phpcsFile->fixer->endChangeset();
        }

        $this->lastImport = $currentImport;
        $this->lastLine   = $line;
    }

    /**
     * Get the import class name for use statement pointed by $stackPtr.
     *
     * @return false|array{startPtr: int, content: string}
     */
    private function getUseImport(File $phpcsFile, int $stackPtr): false|array
    {
        $importTokens = [
            T_NS_SEPARATOR,
            T_STRING,
        ];

        $start = $phpcsFile->findNext(
            PHP_CodeSniffer_Tokens::$emptyTokens,
            $stackPtr + 1,
            null,
            true,
        );

        // $start is false when "use" is the last token in file...
        if ($start === false) {
            return false;
        }

        return [
            'startPtr' => $start,
            'content'  => $phpcsFile->getTokensAsString(
                $start,
                (int) $phpcsFile->findNext($importTokens, $start, null, true) - $start,
            ),
        ];
    }

    /** Get the full use statement as string, including trailing white space. */
    private function getUseStatementAsString(File $phpcsFile, int $stackPtr): string
    {
        $tokens = $phpcsFile->getTokens();

        $useEndPtr = (int) $phpcsFile->findNext([T_SEMICOLON], $stackPtr + 2);
        $useLength = $useEndPtr - $stackPtr + 1;

        if ($tokens[$useEndPtr + 1]['code'] === T_WHITESPACE) {
            $useLength++;
        }

        return $phpcsFile->getTokensAsString($stackPtr, $useLength);
    }

    /**
     * Check if "use" token is not used for import.
     * E.g. function () use () {...}.
     */
    private function checkIsNonImportUse(File $phpcsFile, int $stackPtr): bool
    {
        $tokens = $phpcsFile->getTokens();

        $prev = $phpcsFile->findPrevious(
            PHP_CodeSniffer_Tokens::$emptyTokens,
            $stackPtr - 1,
            0,
            true,
            null,
            true,
        );

        if ($prev !== false) {
            $prevToken = $tokens[$prev];

            if ($prevToken['code'] === T_CLOSE_PARENTHESIS) {
                return true;
            }
        }

        return false;
    }

    /**
     * Replace all the token in same line as the element pointed to by $stackPtr
     * the by the empty string.
     * This will delete the line.
     */
    private function fixerClearLine(File $phpcsFile, int $stackPtr): void
    {
        $tokens = $phpcsFile->getTokens();
        $line   = $tokens[$stackPtr]['line'];

        for ($i = $stackPtr - 1; isset($tokens[$i]) && $tokens[$i]['line'] === $line; $i--) {
            $phpcsFile->fixer->replaceToken($i, '');
        }

        for ($i = $stackPtr; isset($tokens[$i]) && $tokens[$i]['line'] === $line; $i++) {
            $phpcsFile->fixer->replaceToken($i, '');
        }
    }

    /**
     * Find a new destination pointer for the given import string in current
     * use block.
     */
    private function findNewDestination(File $phpcsFile, int $stackPtr, string $import): int
    {
        $tokens = $phpcsFile->getTokens();

        $line     = $tokens[$stackPtr]['line'];
        $prevLine = false;
        $prevPtr  = $stackPtr;

        do {
            $ptr = $prevPtr;

            // Use $line for the first iteration.
            if ($prevLine !== false) {
                $line = $prevLine;
            }

            $prevPtr = $phpcsFile->findPrevious(T_USE, $ptr - 1);

            if ($prevPtr === false) {
                break;
            }

            $prevLine = $tokens[$prevPtr]['line'];
            // phpcs:disable
            /** @var array<string> $prevImportArr */
            $prevImportArr = $this->getUseImport($phpcsFile, $prevPtr);
            // phpcs:enable
        } while (
            $prevLine === ($line - 1)
            && ($this->compareString($prevImportArr['content'], $import) > 0)
        );

        return $ptr;
    }

    /** Compare namespace strings according defined order function. */
    private function compareString(string $leftImport, string $rightImport): int
    {
        return match ($this->order) {
            'string' => strcmp($leftImport, $rightImport),
            'string-locale' => strcoll($leftImport, $rightImport),
            'string-case-insensitive' => strcasecmp($leftImport, $rightImport),
            default => $this->dictionaryCompare($leftImport, $rightImport),
        };
    }

    /**
     * Lexicographical namespace string compare.
     *
     * Example:
     *   use Doctrine\ORM\Query;
     *   use Doctrine\ORM\Query\Expr;
     *   use Doctrine\ORM\QueryBuilder;
     */
    private function dictionaryCompare(string $leftImport, string $rightImport): int
    {
        $min = min(strlen($leftImport), strlen($rightImport));

        for ($i = 0; $i < $min; $i++) {
            if ($leftImport[$i] === $rightImport[$i]) {
                continue;
            }

            if ($leftImport[$i] === self::NAMESPACE_SEPARATOR_STRING) {
                return -1;
            }

            if ($rightImport[$i] === self::NAMESPACE_SEPARATOR_STRING) {
                return 1;
            }

            if ($leftImport[$i] < $rightImport[$i]) {
                return -1;
            }

            if ($leftImport[$i] > $rightImport[$i]) {
                return 1;
            }
        }

        return strcmp(substr($leftImport, $min), substr($rightImport, $min));
    }
}
