<?php

declare(strict_types=1);

namespace SymPressCS\SymPress\Helpers;

use PHPCSUtils\Utils\Conditions;
use PHPCSUtils\Utils\FunctionDeclarations;
use PHPCSUtils\Utils\Scopes;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Util\Tokens;

/**
 * @phpstan-type PhpCsToken array{
 *     code?: int|string,
 *     content?: string,
 *     parenthesis_closer?: int,
 * }
 * @phpstan-type PhpCsTokens array<int, PhpCsToken>
 */
final class Functions
{
    private const int INVALID_POSITION = -1;
    private const string PHP_VERSION_80 = '8.0';
    private const string PHP_VERSION_81 = '8.1';
    private const string PHP_VERSION_82 = '8.2';
    private const string PSR_NAMESPACE_PREFIX = '\\psr\\';
    private const string TYPE_MIXED = 'mixed';
    private const string TYPE_NEVER = 'never';
    private const string TYPE_NULL = 'null';

    private const array ARRAY_ACCESS_METHODS = [
        'offsetExists',
        'offsetGet',
        'offsetSet',
        'offsetUnset',
    ];

    private const array NON_CALL_PREVIOUS_TOKENS = [
        T_NEW,
        T_FUNCTION,
    ];

    private const array OBJECT_SCOPE_TOKENS = [
        T_CLASS,
        T_ANON_CLASS,
    ];

    public static function looksLikeFunctionCall(File $file, int $position): bool
    {
        /** @var PhpCsTokens $tokens */
        $tokens = $file->getTokens();

        if (!self::isPotentialCallName($file, $tokens, $position)) {
            return false;
        }

        $callOpen = self::nextMeaningfulToken($file, $position + 1);
        if ($callOpen === null || self::tokenCode($tokens, $callOpen) !== T_OPEN_PARENTHESIS) {
            return false;
        }

        if (self::isInstantiationOrDeclaration($file, $tokens, $position)) {
            return false;
        }

        return self::hasMatchedCallParentheses($file, $tokens, $callOpen);
    }

    public static function isArrayAccess(File $file, int $position): bool
    {
        if (!Scopes::isOOMethod($file, $position)) {
            return false;
        }

        return in_array(
            FunctionDeclarations::getName($file, $position),
            self::ARRAY_ACCESS_METHODS,
            true,
        );
    }

    public static function bodyContent(File $file, int $position): string
    {
        [$start, $end] = Boundaries::functionBoundaries($file, $position);
        if ($start < 0 || $end < 0) {
            return '';
        }

        return Misc::tokensSubsetToString($start + 1, $end - 1, $file, []);
    }

    public static function countYieldInBody(File $file, int $position): int
    {
        /** @var PhpCsTokens $tokens */
        $tokens = $file->getTokens();

        if (self::tokenCode($tokens, $position) === T_FN) {
            return 0;
        }

        [$start, $end] = Boundaries::functionBoundaries($file, $position);
        if ($start < 0 || $end <= 0) {
            return 0;
        }

        $pos = $start + 1;

        $found = 0;

        while ($pos < $end) {
            [, $innerFunctionEnd] = Boundaries::functionBoundaries($file, $pos);
            if ($innerFunctionEnd > 0) {
                $pos = $innerFunctionEnd + 1;
                continue;
            }

            [, $innerClassEnd] = Boundaries::objectBoundaries($file, $pos);
            if ($innerClassEnd > 0) {
                $pos = $innerClassEnd + 1;
                continue;
            }

            if (in_array(self::tokenCode($tokens, $pos), [T_YIELD, T_YIELD_FROM], true)) {
                $found++;
            }

            $pos++;
        }

        return $found;
    }

    public static function isPsrMethod(File $file, int $position): bool
    {
        if (!Scopes::isOOMethod($file, $position)) {
            return false;
        }

        $classPos = Conditions::getLastCondition($file, $position, self::OBJECT_SCOPE_TOKENS);
        if ($classPos === false) {
            return false;
        }

        /** @var PhpCsTokens $tokens */
        $tokens = $file->getTokens();

        if (!in_array(self::tokenCode($tokens, $classPos), self::OBJECT_SCOPE_TOKENS, true)) {
            return false;
        }

        return self::hasPsrInterface($file, $classPos);
    }

    /**
     * Sometimes we don't declare the type because we can't. For example, if the type is "mixed" or
     * it is union, and we are using PHP 7.4.
     * In those cases, we expect to document the type via doc block, and this functions aims
     * to return true.
     *
     * @param list<string> $docTypes
     * @param bool $return
     */
    public static function isNonDeclarableDocBlockType(array $docTypes, bool $return): bool
    {
        if (!$docTypes) {
            return false;
        }

        $minVer = Misc::getMinPhpTestVersion();

        if (in_array(self::TYPE_NEVER, $docTypes, true)) {
            return $return && !self::phpVersionAtLeast($minVer, self::PHP_VERSION_81);
        }

        if (count($docTypes) > 1 && in_array(self::TYPE_MIXED, $docTypes, true)) {
            return false;
        }

        $effectiveDocTypes = self::withoutNullType($docTypes);

        if (count($effectiveDocTypes) > 1) {
            return self::isNonDeclarableUnionDocBlockType($effectiveDocTypes, $minVer);
        }

        return self::isNonDeclarableSingleDocBlockType((string) reset($effectiveDocTypes), $return, $minVer);
    }

    /**
     * Sometimes we don't declare the type because we can't. For example, if the type is "mixed" or
     * it is union, and we are using PHP 7.4.
     * In those cases, we expect to document the type via doc block, and this functions aims
     * to return true.
     *
     * @param string $docType
     * @param bool $return
     */
    private static function isNonDeclarableSingleDocBlockType(
        string $docType,
        bool $return,
        string $minPhpVersion,
    ): bool {

        if ($docType === self::TYPE_NEVER) {
            return $return && !self::phpVersionAtLeast($minPhpVersion, self::PHP_VERSION_81);
        }

        return match (true) {
            $docType === self::TYPE_MIXED => !self::phpVersionAtLeast($minPhpVersion, self::PHP_VERSION_80),
            $docType === self::TYPE_NULL => !self::phpVersionAtLeast($minPhpVersion, self::PHP_VERSION_82),
            self::containsIntersectionType([$docType]) => !self::phpVersionAtLeast($minPhpVersion, self::PHP_VERSION_81),
            default => false,
        };
    }

    /** @param PhpCsTokens $tokens */
    private static function isPotentialCallName(File $file, array $tokens, int $position): bool
    {
        $code = self::tokenCode($tokens, $position);

        return in_array($code, self::functionNameTokens(), true)
            && ($code !== T_VARIABLE || !Scopes::isOOProperty($file, $position));
    }

    /** @return list<int|string> */
    private static function functionNameTokens(): array
    {
        return array_merge(array_keys(Tokens::$functionNameTokens), [T_VARIABLE]);
    }

    /** @param PhpCsTokens $tokens */
    private static function isInstantiationOrDeclaration(File $file, array $tokens, int $position): bool
    {
        return in_array(
            self::tokenCode($tokens, self::previousMeaningfulBeforeCallName($file, $tokens, $position)),
            self::NON_CALL_PREVIOUS_TOKENS,
            true,
        );
    }

    /** @param PhpCsTokens $tokens */
    private static function previousMeaningfulBeforeCallName(File $file, array $tokens, int $position): ?int
    {
        $previous = self::previousMeaningfulToken($file, Tokens::$emptyTokens, $position - 1);

        if ($previous === null || self::tokenCode($tokens, $previous) !== T_NS_SEPARATOR) {
            return $previous;
        }

        return self::previousMeaningfulToken(
            $file,
            array_merge(Tokens::$emptyTokens, [T_STRING, T_NS_SEPARATOR]),
            $previous - 1,
        );
    }

    private static function nextMeaningfulToken(File $file, int $start): ?int
    {
        return self::normalizePosition(
            $file->findNext(Tokens::$emptyTokens, $start, null, true, null, true),
        );
    }

    /** @param array<int|string, int|string> $excludedTokens */
    private static function previousMeaningfulToken(File $file, array $excludedTokens, int $start): ?int
    {
        return self::normalizePosition(
            $file->findPrevious($excludedTokens, $start, null, true, null, true),
        );
    }

    private static function normalizePosition(int|false $position): ?int
    {
        return $position === false ? null : $position;
    }

    /** @param PhpCsTokens $tokens */
    private static function hasMatchedCallParentheses(File $file, array $tokens, int $callOpen): bool
    {
        return $file->findNext([T_CLOSE_PARENTHESIS], $callOpen + 1, null, false, null, true)
            === ($tokens[$callOpen]['parenthesis_closer'] ?? self::INVALID_POSITION);
    }

    private static function hasPsrInterface(File $file, int $classPosition): bool
    {
        foreach (Objects::allInterfacesFullyQualifiedNames($file, $classPosition) ?? [] as $interface) {
            if (str_starts_with(strtolower($interface), self::PSR_NAMESPACE_PREFIX)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param list<string> $docTypes
     * @return list<string>
     */
    private static function withoutNullType(array $docTypes): array
    {
        return array_values(array_filter(
            $docTypes,
            static fn (string $docType): bool => $docType !== self::TYPE_NULL,
        ));
    }

    /** @param list<string> $docTypes */
    private static function isNonDeclarableUnionDocBlockType(array $docTypes, string $minPhpVersion): bool
    {
        return !self::phpVersionAtLeast($minPhpVersion, self::PHP_VERSION_80)
            || (
                self::containsIntersectionType($docTypes)
                && !self::phpVersionAtLeast($minPhpVersion, self::PHP_VERSION_82)
            );
    }

    /** @param list<string> $docTypes */
    private static function containsIntersectionType(array $docTypes): bool
    {
        foreach ($docTypes as $docType) {
            if (str_contains($docType, '&')) {
                return true;
            }
        }

        return false;
    }

    private static function phpVersionAtLeast(string $minPhpVersion, string $version): bool
    {
        return version_compare($minPhpVersion, $version, '>=');
    }

    /** @param PhpCsTokens $tokens */
    private static function tokenCode(array $tokens, ?int $position): int|string|null
    {
        return $position === null ? null : ($tokens[$position]['code'] ?? null);
    }
}
