<?php

declare(strict_types=1);

namespace SymPressCS\SymPress\Sniffs\Classes;

use PHPCSUtils\Utils\Scopes;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;

final class AccessorNamingSniff implements Sniff
{
    public const ALLOWED_NAMES = [
        '__call',
        '__callStatic',
        '__clone',
        '__construct',
        '__debugInfo',
        '__destruct',
        '__get',
        '__invoke',
        '__isset',
        '__serialize',
        '__set',
        '__set_state',
        '__sleep',
        '__toString',
        '__unserialize',
        '__unset',
        '__wakeup',
        'count',
        'current',
        'getChildren',
        'getInnerIterator',
        'getIterator',
        'jsonSerialize',
        'key',
        'next',
        'process',
        'register',
        'rewind',
        'setUp',
        'setUpBeforeClass',
        'tearDown',
        'tearDownAfterClass',
        'valid',
    ];

    private const ACTION_PREFIXES = [
        'add',
        'align',
        'apply',
        'assert',
        'build',
        'calculate',
        'check',
        'collect',
        'compare',
        'compile',
        'compute',
        'convert',
        'create',
        'decode',
        'dispatch',
        'encode',
        'extract',
        'filter',
        'find',
        'fix',
        'format',
        'handle',
        'hydrate',
        'load',
        'make',
        'map',
        'matches',
        'normalize',
        'parse',
        'prepare',
        'provide',
        'read',
        'render',
        'report',
        'resolve',
        'sort',
        'supports',
        'transform',
        'validate',
        'write',
    ];

    private const BOOLEAN_PREFIXES = [
        'allows',
        'can',
        'contains',
        'denies',
        'exists',
        'has',
        'is',
        'matches',
        'needs',
        'should',
        'supports',
        'uses',
    ];

    private const SETTER_LIKE_PREFIXES = [
        'change',
        'rename',
        'replace',
        'update',
        'with',
    ];

    public bool $skipForPrivate = true;

    public bool $skipForProtected = false;

    /** @return list<int|string> */
    public function register(): array
    {
        return [
            T_FUNCTION,
        ];
    }

    /**
     * @param File $phpcsFile
     * @param int $stackPtr
     */
    public function process(File $phpcsFile, $stackPtr): void
    {
        if (!Scopes::isOOMethod($phpcsFile, $stackPtr)) {
            return;
        }

        $functionName = $phpcsFile->getDeclarationName($stackPtr) ?? '';

        if (($functionName === '') || in_array($functionName, self::ALLOWED_NAMES, true)) {
            return;
        }

        if (str_ends_with($functionName, 'Provider')) {
            return;
        }

        if ($this->shouldSkip($phpcsFile, $stackPtr)) {
            return;
        }

        $method = $this->methodShape($phpcsFile, $stackPtr);

        $this->checkReadAccessor($phpcsFile, $stackPtr, $functionName, $method);
        $this->checkWriteAccessor($phpcsFile, $stackPtr, $functionName, $method);
    }

    /**
     * @param array{
     *     parameterNames: list<string>,
     *     parameterCount: int,
     *     returnType: string,
     *     scopeOpener: int|null,
     *     scopeCloser: int|null
     * } $method
     */
    private function checkReadAccessor(File $file, int $position, string $methodName, array $method): void
    {
        if ($this->isGetterName($methodName) || $method['parameterCount'] !== 0) {
            return;
        }

        $propertyName = $this->returnedObjectProperty($file, $method);

        if (
            $propertyName === null
            && (
                $method['scopeOpener'] !== null
                || !$this->isPropertyLikeReadName($methodName, $method['returnType'])
            )
        ) {
            return;
        }

        $sourceName = $propertyName ?? $methodName;
        $getterName = $this->getterNameFor($sourceName, $method['returnType']);

        $file->addWarning(
            'Data-read accessors should use getter naming. Rename "%s()" to "%s()".',
            $position,
            'GetterRequired',
            [$methodName, $getterName],
        );
    }

    /**
     * @param array{
     *     parameterNames: list<string>,
     *     parameterCount: int,
     *     returnType: string,
     *     scopeOpener: int|null,
     *     scopeCloser: int|null
     * } $method
     */
    private function checkWriteAccessor(File $file, int $position, string $methodName, array $method): void
    {
        if ($this->isSetterName($methodName) || $method['parameterCount'] === 0) {
            return;
        }

        $propertyName = $this->assignedObjectPropertyFromParameter($file, $method);

        if (
            $propertyName === null
            && (
                $method['scopeOpener'] !== null
                || !$this->isSetterLikeName($methodName)
            )
        ) {
            return;
        }

        $sourceName = $propertyName ?? $this->setterSubject($methodName);
        $setterName = 'set' . $this->studlyName($sourceName);

        $file->addWarning(
            'Data-write accessors should use setter naming. Rename "%s()" to "%s()".',
            $position,
            'SetterRequired',
            [$methodName, $setterName],
        );
    }

    /**
     * @return array{
     *     parameterNames: list<string>,
     *     parameterCount: int,
     *     returnType: string,
     *     scopeOpener: int|null,
     *     scopeCloser: int|null
     * }
     */
    private function methodShape(File $file, int $position): array
    {
        $tokens = $file->getTokens();

        $openParenthesis = (int) $tokens[$position]['parenthesis_opener'];
        $closeParenthesis = (int) $tokens[$position]['parenthesis_closer'];

        $parameterNames = $this->parameterNames($file, $openParenthesis, $closeParenthesis);

        return [
            'parameterNames' => $parameterNames,
            'parameterCount' => count($parameterNames),
            'returnType'     => $this->returnType($file, $closeParenthesis, $position),
            'scopeOpener'    => isset($tokens[$position]['scope_opener'])
                ? (int) $tokens[$position]['scope_opener']
                : null,
            'scopeCloser'    => isset($tokens[$position]['scope_closer'])
                ? (int) $tokens[$position]['scope_closer']
                : null,
        ];
    }

    /** @return list<string> */
    private function parameterNames(File $file, int $openParenthesis, int $closeParenthesis): array
    {
        $tokens = $file->getTokens();
        $names = [];

        for ($i = $openParenthesis + 1; $i < $closeParenthesis; $i++) {
            if ($tokens[$i]['code'] !== T_VARIABLE) {
                continue;
            }

            $names[] = $tokens[$i]['content'];
        }

        return $names;
    }

    private function returnType(File $file, int $closeParenthesis, int $functionPosition): string
    {
        $tokens = $file->getTokens();
        $end = $tokens[$functionPosition]['scope_opener'] ?? $file->findNext(T_SEMICOLON, $closeParenthesis + 1);

        if (!is_int($end)) {
            return '';
        }

        $colon = $file->findNext(Tokens::$emptyTokens, $closeParenthesis + 1, $end, true);

        if (!is_int($colon) || $tokens[$colon]['code'] !== T_COLON) {
            return '';
        }

        return trim($file->getTokensAsString($colon + 1, $end - $colon - 1));
    }

    /**
     * @param array{
     *     parameterNames: list<string>,
     *     parameterCount: int,
     *     returnType: string,
     *     scopeOpener: int|null,
     *     scopeCloser: int|null
     * } $method
     */
    private function returnedObjectProperty(File $file, array $method): ?string
    {
        if ($method['scopeOpener'] === null || $method['scopeCloser'] === null) {
            return null;
        }

        $tokens = $file->getTokens();
        $return = $file->findNext(Tokens::$emptyTokens, $method['scopeOpener'] + 1, $method['scopeCloser'], true);

        if (!is_int($return) || $tokens[$return]['code'] !== T_RETURN) {
            return null;
        }

        $statementEnd = $file->findNext(T_SEMICOLON, $return + 1, $method['scopeCloser']);

        if (
            !is_int($statementEnd)
            || !$this->onlyEmptyTokensBetween($file, $statementEnd + 1, $method['scopeCloser'])
        ) {
            return null;
        }

        $thisPointer = $file->findNext(Tokens::$emptyTokens, $return + 1, $statementEnd, true);

        if (!$this->isThisVariable($tokens, $thisPointer)) {
            return null;
        }

        $objectOperator = $file->findNext(Tokens::$emptyTokens, $thisPointer + 1, $statementEnd, true);

        if (!is_int($objectOperator) || $tokens[$objectOperator]['code'] !== T_OBJECT_OPERATOR) {
            return null;
        }

        $propertyPointer = $file->findNext(Tokens::$emptyTokens, $objectOperator + 1, $statementEnd, true);

        if (!is_int($propertyPointer) || $tokens[$propertyPointer]['code'] !== T_STRING) {
            return null;
        }

        return $file->findNext(Tokens::$emptyTokens, $propertyPointer + 1, $statementEnd, true) === false
            ? $tokens[$propertyPointer]['content']
            : null;
    }

    /**
     * @param array{
     *     parameterNames: list<string>,
     *     parameterCount: int,
     *     returnType: string,
     *     scopeOpener: int|null,
     *     scopeCloser: int|null
     * } $method
     */
    private function assignedObjectPropertyFromParameter(File $file, array $method): ?string
    {
        if ($method['scopeOpener'] === null || $method['scopeCloser'] === null) {
            return null;
        }

        $tokens = $file->getTokens();
        $parameters = array_flip($method['parameterNames']);

        $thisPointer = $file->findNext(Tokens::$emptyTokens, $method['scopeOpener'] + 1, $method['scopeCloser'], true);

        if (!is_int($thisPointer) || !$this->isThisVariable($tokens, $thisPointer)) {
            return null;
        }

        $propertyName = $this->assignedPropertyName($file, $thisPointer, $method['scopeCloser']);

        if ($propertyName === null) {
            return null;
        }

        $assignment = $file->findNext(T_EQUAL, $thisPointer + 1, $method['scopeCloser']);

        if (
            !is_int($assignment)
            || !$this->statementUsesParameter($file, $assignment, $parameters, $method['scopeCloser'])
        ) {
            return null;
        }

        $statementEnd = $file->findNext(T_SEMICOLON, $assignment + 1, $method['scopeCloser']);

        if (
            !is_int($statementEnd)
            || !$this->onlyEmptyTokensBetween($file, $statementEnd + 1, $method['scopeCloser'])
        ) {
            return null;
        }

        return $propertyName;
    }

    private function assignedPropertyName(File $file, int $thisPointer, int $scopeCloser): ?string
    {
        $tokens = $file->getTokens();

        $objectOperator = $file->findNext(Tokens::$emptyTokens, $thisPointer + 1, $scopeCloser, true);

        if (!is_int($objectOperator) || $tokens[$objectOperator]['code'] !== T_OBJECT_OPERATOR) {
            return null;
        }

        $propertyPointer = $file->findNext(Tokens::$emptyTokens, $objectOperator + 1, $scopeCloser, true);

        if (!is_int($propertyPointer) || $tokens[$propertyPointer]['code'] !== T_STRING) {
            return null;
        }

        $assignment = $file->findNext(Tokens::$emptyTokens, $propertyPointer + 1, $scopeCloser, true);

        if (!is_int($assignment) || $tokens[$assignment]['code'] !== T_EQUAL) {
            return null;
        }

        return $tokens[$propertyPointer]['content'];
    }

    /** @param array<int, array<string, mixed>> $tokens */
    private function isThisVariable(array $tokens, mixed $position): bool
    {
        return is_int($position)
            && $tokens[$position]['code'] === T_VARIABLE
            && $tokens[$position]['content'] === '$this';
    }

    /** @param array<string, int> $parameters */
    private function statementUsesParameter(File $file, int $assignment, array $parameters, int $scopeCloser): bool
    {
        $tokens = $file->getTokens();
        $statementEnd = $file->findNext(T_SEMICOLON, $assignment + 1, $scopeCloser);

        if (!is_int($statementEnd)) {
            return false;
        }

        for ($i = $assignment + 1; $i < $statementEnd; $i++) {
            if ($tokens[$i]['code'] !== T_VARIABLE) {
                continue;
            }

            if (isset($parameters[$tokens[$i]['content']])) {
                return true;
            }
        }

        return false;
    }

    private function shouldSkip(File $file, int $position): bool
    {
        if (!$this->skipForPrivate && !$this->skipForProtected) {
            return false;
        }

        $properties = $file->getMethodProperties($position);
        $modifier   = $properties['scope'] ?? '';

        if ($modifier === 'private' && $this->skipForPrivate) {
            return true;
        }

        return $modifier === 'protected' && $this->skipForProtected;
    }

    private function isGetterName(string $methodName): bool
    {
        return $this->startsWithAccessorPrefix($methodName, ['get', ...self::BOOLEAN_PREFIXES]);
    }

    private function isSetterName(string $methodName): bool
    {
        return $this->startsWithAccessorPrefix($methodName, ['set']);
    }

    /** @param list<string> $prefixes */
    private function startsWithAccessorPrefix(string $methodName, array $prefixes): bool
    {
        foreach ($prefixes as $prefix) {
            if (!str_starts_with($methodName, $prefix)) {
                continue;
            }

            $next = $methodName[strlen($prefix)] ?? '';

            if ($next === '' || ctype_upper($next) || ctype_digit($next) || $next === '_') {
                return true;
            }
        }

        return false;
    }

    private function isPropertyLikeReadName(string $methodName, string $returnType): bool
    {
        if ($returnType === '' || $this->returnsVoid($returnType) || $this->hasActionPrefix($methodName)) {
            return false;
        }

        return (bool) preg_match('/^[a-z][A-Za-z0-9]*$/', $methodName);
    }

    private function isSetterLikeName(string $methodName): bool
    {
        foreach (self::SETTER_LIKE_PREFIXES as $prefix) {
            if ($this->startsWithAccessorPrefix($methodName, [$prefix])) {
                return true;
            }
        }

        return false;
    }

    private function hasActionPrefix(string $methodName): bool
    {
        foreach ([...self::ACTION_PREFIXES, ...self::SETTER_LIKE_PREFIXES] as $prefix) {
            if ($this->startsWithAccessorPrefix($methodName, [$prefix])) {
                return true;
            }
        }

        return false;
    }

    private function returnsVoid(string $returnType): bool
    {
        return in_array(strtolower(trim($returnType, " \t\n\r\0\x0B?")), ['never', 'void'], true);
    }

    private function returnsBool(string $returnType): bool
    {
        $normalized = strtolower(trim($returnType, " \t\n\r\0\x0B?"));

        return $normalized === 'bool' || $normalized === 'boolean';
    }

    private function onlyEmptyTokensBetween(File $file, int $start, int $end): bool
    {
        if ($start >= $end) {
            return true;
        }

        return $file->findNext(Tokens::$emptyTokens, $start, $end, true) === false;
    }

    private function getterNameFor(string $sourceName, string $returnType): string
    {
        return ($this->returnsBool($returnType) ? 'is' : 'get') . $this->studlyName($sourceName);
    }

    private function setterSubject(string $methodName): string
    {
        foreach (self::SETTER_LIKE_PREFIXES as $prefix) {
            if (!$this->startsWithAccessorPrefix($methodName, [$prefix])) {
                continue;
            }

            return (string) substr($methodName, strlen($prefix));
        }

        return $methodName;
    }

    private function studlyName(string $name): string
    {
        $name = trim($name, '_');

        if ($name === '') {
            return '';
        }

        $name = preg_replace('/[_\s-]+/', ' ', $name) ?? $name;
        $name = str_replace(' ', '', ucwords($name));

        return ucfirst($name);
    }
}
