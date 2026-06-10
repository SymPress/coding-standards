<?php

declare(strict_types=1);

namespace SymPressCS\SymPress\Sniffs\Formatting;

use PHP_CodeSniffer\Exceptions\RuntimeException;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens as PHP_CodeSniffer_Tokens;

use function array_key_exists;
use function array_slice;
use function in_array;
use function preg_split;
use function str_replace;
use function strlen;
use function strpos;
use function substr;
use function trim;

/**
 * Unnecessary Namespace Usage sniff.
 *
 * Full namespace declaration should be skipped in favour of the short declaration.
 */
class UnnecessaryNamespaceUsageSniff implements Sniff
{
    private const DOC_COMMENT_TAGS = [
        '@param'  => 1,
        '@return' => 1,
        '@throws' => 1,
        '@var'    => 2,
    ];

    private const SCAN_TOKENS = [
        T_NS_SEPARATOR,
        T_DOC_COMMENT_OPEN_TAG,
    ];

    /**
     * Tokens used in full class name.
     *
     * @var array<int, int>
     */
    private array $classNameTokens = [
        T_NS_SEPARATOR,
        T_STRING,
    ];

    /**
     * Registers the tokens that this sniff wants to listen for.
     *
     * @see    Tokens.php
     * @return array<int, int>
     */
    public function register(): array
    {
        return [T_CLASS];
    }

    /**
     * Called when one of the token types that this sniff is listening for
     * is found.
     *
     * @param File $phpcsFile The PHP_CodeSniffer file where the
     *                        token was found.
     * @param int  $stackPtr  The position in the PHP_CodeSniffer
     *                        file's token stack where the token
     *                        was found.
     * @throws RuntimeException
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint
     */
    public function process(File $phpcsFile, $stackPtr): void
    {
        $tokens        = $phpcsFile->getTokens();
        $useStatements = $this->useStatements($phpcsFile, 0, $stackPtr - 1);
        $namespace     = $this->namespaceName($phpcsFile, 0, $stackPtr - 1);

        $nsSep = $phpcsFile->findNext(self::SCAN_TOKENS, $stackPtr + 1);

        while ($nsSep !== false) {
            $classNameEnd = $phpcsFile->findNext(
                $this->classNameTokens,
                $nsSep,
                null,
                true,
            );

            if ($classNameEnd === false) {
                break;
            }

            if ($tokens[$nsSep]['code'] === T_NS_SEPARATOR) {
                $this->checkNamespaceSeparator(
                    $phpcsFile,
                    $useStatements,
                    $namespace,
                    $nsSep,
                    (int) $classNameEnd,
                );

                $nsSep = $phpcsFile->findNext(self::SCAN_TOKENS, (int) $classNameEnd + 1);

                continue;
            }

            $this->checkDocComment($phpcsFile, $useStatements, $namespace, $nsSep);

            $nsSep = $phpcsFile->findNext(self::SCAN_TOKENS, (int) $classNameEnd + 1);
        }
    }

    /** @param array<string, string> $useStatements */
    private function checkNamespaceSeparator(
        File $phpcsFile,
        array $useStatements,
        string $namespace,
        int $namespaceSeparator,
        int $classNameEnd,
    ): void {

        $tokens = $phpcsFile->getTokens();

        if ($tokens[$namespaceSeparator - 1]['code'] === T_STRING) {
            --$namespaceSeparator;
        }

        $className = $phpcsFile->getTokensAsString(
            $namespaceSeparator,
            $classNameEnd - $namespaceSeparator,
        );

        $this->checkShorthandPossible(
            $phpcsFile,
            $useStatements,
            $className,
            $namespace,
            $namespaceSeparator,
            $classNameEnd - 1,
        );
    }

    /**
     * @param array<string, string> $useStatements
     * @throws RuntimeException
     */
    private function checkDocComment(
        File $phpcsFile,
        array $useStatements,
        string $namespace,
        int $docCommentOpenPtr,
    ): void {

        $tokens = $phpcsFile->getTokens();

        foreach ($tokens[$docCommentOpenPtr]['comment_tags'] as $tag) {
            $content = $tokens[$tag]['content'];

            if (array_key_exists($content, self::DOC_COMMENT_TAGS) === false) {
                continue;
            }

            $this->checkDocCommentTag($phpcsFile, $useStatements, $namespace, $tag, $content);
        }
    }

    /**
     * @param array<string, string> $useStatements
     * @throws RuntimeException
     */
    private function checkDocCommentTag(
        File $phpcsFile,
        array $useStatements,
        string $namespace,
        int $tag,
        string $content,
    ): void {

        $tokens = $phpcsFile->getTokens();
        $next   = $tag + 1;

        // PHP_CodeSniffer adds T_DOC_COMMENT_CLOSE_TAG with empty string content.
        $lineEnd = $phpcsFile->findNext(
            [
                T_DOC_COMMENT_CLOSE_TAG,
                T_DOC_COMMENT_STAR,
            ],
            $next,
        );

        $docCommentStringPtr = $phpcsFile->findNext(
            [T_DOC_COMMENT_STRING],
            $next,
            (int) $lineEnd,
        );

        if ($docCommentStringPtr === false) {
            return;
        }

        $docLineTokens = preg_split(
            '/\s+/',
            $tokens[$docCommentStringPtr]['content'],
            -1,
            PREG_SPLIT_NO_EMPTY,
        );

        if ($docLineTokens === false) {
            throw new RuntimeException(
                'Unexpected Error in SymPress Coding Standards.',
            );
        }

        $docLineTokens = array_slice($docLineTokens, 0, self::DOC_COMMENT_TAGS[$content]);

        foreach ($docLineTokens as $docLineToken) {
            $this->checkDocLineToken($phpcsFile, $useStatements, $namespace, $docCommentStringPtr, $docLineToken);
        }
    }

    /**
     * @param array<string, string> $useStatements
     * @throws RuntimeException
     */
    private function checkDocLineToken(
        File $phpcsFile,
        array $useStatements,
        string $namespace,
        int $docCommentStringPtr,
        string $docLineToken,
    ): void {

        $typeTokens = preg_split('/\|/', $docLineToken, -1, PREG_SPLIT_NO_EMPTY);

        if ($typeTokens === false) {
            throw new RuntimeException(
                'Unexpected Error in SymPress Coding Standards.',
            );
        }

        foreach ($typeTokens as $typeToken) {
            if (in_array($typeToken, $useStatements, true) === true) {
                continue;
            }

            $this->checkShorthandPossible(
                $phpcsFile,
                $useStatements,
                $typeToken,
                $namespace,
                $docCommentStringPtr,
                $docCommentStringPtr,
                true,
            );
        }
    }

    /**
     * Collect all use statements in range.
     *
     * @param File $phpcsFile PHP CS File
     * @param int  $start     start pointer
     * @param int  $end       end pointer
     * @return array<string, string>
     */
    protected function useStatements(File $phpcsFile, int $start, int $end): array
    {
        $useStatements = [];
        $i             = $start;
        $tokens        = $phpcsFile->getTokens();
        $useTokenPtr   = $phpcsFile->findNext(T_USE, $i, $end);

        while ($useTokenPtr !== false) {
            $classNameStart = $phpcsFile->findNext(
                PHP_CodeSniffer_Tokens::$emptyTokens,
                $useTokenPtr + 1,
                $end,
                true,
            );

            if (
                $classNameStart === false
                || in_array($tokens[$classNameStart]['code'], [T_CONST, T_FUNCTION], true) === true
                || $tokens[$classNameStart]['content'] === '('
            ) {
                $i           = $useTokenPtr + 1;
                $useTokenPtr = $phpcsFile->findNext(T_USE, $i, $end);

                continue;
            }

            $classNameEnd = $phpcsFile->findNext(
                $this->classNameTokens,
                (int) $classNameStart + 1,
                $end,
                true,
            );

            if ($classNameEnd === false || $classNameEnd <= $classNameStart) {
                $i           = (int) $classNameStart + 1;
                $useTokenPtr = $phpcsFile->findNext(T_USE, $i, $end);

                continue;
            }

            $useEnd = $phpcsFile->findNext(
                [
                    T_SEMICOLON,
                    T_COMMA,
                ],
                (int) $classNameEnd,
                $end,
            );

            // Prevent endless loop when 'use ;' is the last use statement.
            if ($useEnd === false) {
                break;
            }

            /** @var int $aliasNamePtr */
            $aliasNamePtr = $phpcsFile->findPrevious(
                PHP_CodeSniffer_Tokens::$emptyTokens,
                $useEnd - 1,
                0,
                true,
            );

            $length    = (int) $classNameEnd - (int) $classNameStart;
            $className = trim($phpcsFile->getTokensAsString((int) $classNameStart, $length));

            if ($className === '') {
                $i           = $useEnd + 1;
                $useTokenPtr = $tokens[$useEnd]['code'] === T_COMMA ? $i : $phpcsFile->findNext(T_USE, $i, $end);

                continue;
            }

            $className                 = $this->fullyQualifiedClassName($className);
            $useStatements[$className] = $tokens[$aliasNamePtr]['content'];
            $i                         = $useEnd + 1;

            $useTokenPtr = $tokens[$useEnd]['code'] === T_COMMA ? $i : $phpcsFile->findNext(T_USE, $i, $end);
        }

        return $useStatements;
    }

    /**
     * Read the namespace of the current class file.
     *
     * @param File $phpcsFile PHP CS File
     * @param int  $start     start pointer
     * @param int  $end       end pointer
     */
    protected function namespaceName(File $phpcsFile, int $start, int $end): string
    {
        $namespace = $phpcsFile->findNext(T_NAMESPACE, $start, $end);

        if ($namespace === false) {
            return '';
        }

        $namespaceStart = $phpcsFile->findNext(
            PHP_CodeSniffer_Tokens::$emptyTokens,
            (int) $namespace + 1,
            $end,
            true,
        );

        if ($namespaceStart === false) {
            return '';
        }

        $namespaceEnd = $phpcsFile->findNext(
            $this->classNameTokens,
            (int) $namespaceStart + 1,
            $end,
            true,
        );

        if ($namespaceEnd === false || $namespaceEnd <= $namespaceStart) {
            return '';
        }

        $nslen = (int) $namespaceEnd - (int) $namespaceStart;
        $name  = $phpcsFile->getTokensAsString((int) $namespaceStart, $nslen);

        return "\\{$name}\\";
    }

    /**
     * Return the fully qualified class name, e.g. '\Foo\Bar\Faz'.
     *
     * @param string $className class name
     */
    private function fullyQualifiedClassName(string $className): string
    {
        if ($className === '') {
            return '';
        }

        return $className[0] !== '\\' ? "\\{$className}" : $className;
    }

    /**
     * Check if short hand is possible.
     *
     * @param File   $phpcsFile     PHP CS File
     * @param array<string, string> $useStatements array with class use statements
     * @param string $className     class name
     * @param string $namespace     name space
     * @param int    $startPtr      start token pointer
     * @param int    $endPtr        end token pointer
     * @param bool   $isDocBlock    true if fixing doc block
     */
    private function checkShorthandPossible(File $phpcsFile, array $useStatements, string $className, string $namespace, int $startPtr, int $endPtr, bool $isDocBlock = false): void
    {
        $msg              = 'Shorthand possible. Replace "%s" with "%s"';
        $code             = 'UnnecessaryNamespaceUsage';
        $fixable          = false;
        $replaceClassName = false;
        $replacement      = '';

        $fullClassName = $this->fullyQualifiedClassName($className);

        if (array_key_exists($fullClassName, $useStatements) === true) {
            $replacement = $useStatements[$fullClassName];

            $data = [
                $className,
                $replacement,
            ];

            $fixable = $phpcsFile->addFixableWarning(
                $msg,
                $startPtr,
                $code,
                $data,
            );

            $replaceClassName = true;
        } elseif ($namespace !== '' && strpos($fullClassName, $namespace) === 0) {
            $replacement = substr($fullClassName, strlen($namespace));

            $data    = [
                $className,
                $replacement,
            ];
            $fixable = $phpcsFile->addFixableWarning(
                $msg,
                $startPtr,
                $code,
                $data,
            );
        }

        if ($fixable !== true) {
            return;
        }

        $phpcsFile->fixer->beginChangeset();

        if ($isDocBlock === true) {
            $tokens     = $phpcsFile->getTokens();
            $oldContent = $tokens[$startPtr]['content'];
            $newContent = str_replace($className, $replacement, $oldContent);
            $phpcsFile->fixer->replaceToken($startPtr, $newContent);
            $phpcsFile->fixer->endChangeset();

            return;
        }

        for ($i = $startPtr; $i < $endPtr; $i++) {
            $phpcsFile->fixer->replaceToken($i, '');
        }

        if ($replaceClassName === true) {
            $phpcsFile->fixer->replaceToken($endPtr, $replacement);
        }

        $phpcsFile->fixer->endChangeset();
    }
}
